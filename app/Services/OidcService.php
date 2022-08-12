<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\OAuthValidationException;
use App\Services\Oidc\ClientResolverInterface;
use App\Services\Oidc\StorageInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class OidcService
{
    protected ClientResolverInterface $clientResolver;
    protected StorageInterface $storage;
    protected JwtService $jwtService;

    public function __construct(
        ClientResolverInterface $clientResolver,
        StorageInterface $storage,
        JwtService $jwtService
    ) {
        $this->clientResolver = $clientResolver;
        $this->storage = $storage;
        $this->jwtService = $jwtService;
    }

    public function authorize(Request $request): RedirectResponse
    {
        $oidcParams = OidcParams::fromArray($request->all());

        try {
            $this->validateParams($oidcParams);
        } catch (OAuthValidationException $e) {
            // Clear current session
            if ($request->hasSession()) {
                $request->session()->flush();
            }

            // If the error allows us to redirect to the callback, do so
            if ($e->canRedirect()) {
                return $this->createErrorRedirect($request, $oidcParams, $e->getMessage());
            }

            // Otherwise, return generic enough error not to leak info
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        // Start or replace session
        if (!$oidcParams->has('lang')) {
            $oidcParams->set('lang', App::getLocale());
        }

        // Store client object
        $client = $this->clientResolver->resolve($oidcParams->clientId);
        $oidcParams->set('client', $client);

        $request->session()->flush();
        $request->session()->put('oidcparams', $oidcParams);

        return Redirect::route('start_auth');
    }

    protected function createErrorRedirect(Request $request, OidcParams $params, string $error): RedirectResponse
    {
        $qs = http_build_query([
            'state' => $params->state ?? '',
            'error' => $error,
        ]);
        $redirectUri = $params->redirectUri . '?' . $qs;

        return new RedirectResponse($redirectUri);
    }

    public function finishAuthorize(Request $request, string $hash): RedirectResponse
    {
        $oidcParams = $request->session()->get('oidcparams');
        $oidcParams->set('hash', $hash);

        // Create authentication code and cache the request vars for when the auth code is used by the client
        $authCode = $this->generateAuthCode();
        $this->storage->saveAuthData($authCode, $oidcParams->toArray());

        // Clear current session
        $request->session()->flush();

        $qs = http_build_query([
            'state' => $oidcParams->state,
            'code' => $authCode,
        ]);
        $redirectUri = $oidcParams->redirectUri . '?' . $qs;

        // TODO: reset auth code expiry?

        return new RedirectResponse($redirectUri);
    }

    public function hasAuthorizeSession(Request $request): bool
    {
        if ($request->session()->has('oidcparams')) {
            return true;
        }

        Log::warning("hasAuthorizeSession: cannot find all authorization parameters");

        $locale = App::getLocale();
        $request->session()->flush();
        $request->session()->put('lang', $locale);
        return false;
    }

    public function validateParams(OidcParams $params): void
    {
        $validator = Validator::make($params->toArray(), [
            'client_id' => ['required', 'string'],
            'redirect_uri' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            throw OAuthValidationException::invalidRequest(false);
        }

        // Check if the client-id matches something we can accept, and check if
        // the redirect_uri is valid for the client_id
        $client = $this->clientResolver->resolve($params->clientId);
        if (!$client) {
            throw OAuthValidationException::unauthorizedClient(false);
        }
        if (!in_array($params->redirectUri, $client->getRedirectUris(), true)) {
            throw OAuthValidationException::invalidRedirectUri(false);
        }

        $validator = Validator::make($params->toArray(), [
            'response_type' => ['required', 'string'],
            'state' => ['required', 'string'],
            'scope' => ['required', 'string'],
            'code_challenge' => ['required', 'string'],
            'code_challenge_method' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            throw OAuthValidationException::invalidRequest(true);
        }

        if ($params->responseType !== "code") {
            throw OAuthValidationException::unsupportedResponseType(true);
        }

        if ($params->scope !== "openid") {
            throw OAuthValidationException::invalidScope(true);
        }

        if (empty($params->codeChallenge)) {
            throw OAuthValidationException::invalidRequest(true);
        }

        if ($params->codeChallengeMethod !== "S256") {
            throw OAuthValidationException::invalidRequest(true);
        }

        if (! $this->clientResolver->exists($params->clientId)) {
            throw OAuthValidationException::unauthorizedClient(true);
        }
    }

    public function accessToken(Request $request): JsonResponse
    {
        // Validate request for needed parameters
        $validator = Validator::make($request->all(), [
            'grant_type' => ['required', 'string'],
            'code' => ['required', 'string'],
            'redirect_uri' => ['required', 'string'], // TODO: optional?
            'code_verifier' => ['required', 'string'],
            'client_id' => ['required', 'string'],
        ]);
        if ($validator->fails()) {
            Log::error("accessToken: incomplete set of request data found");
            throw new BadRequestHttpException("incomplete set of request data found");
        }

        // Check params
        if ($request->get('grant_type') !== "authorization_code") {
            Log::error("accessToken: authorization_code expected as response type");
            throw new BadRequestHttpException("authorization_code expected as response type");
        }

        if (empty($request->get('code'))) {
            Log::error("accessToken: code not found");
            throw new BadRequestHttpException("code not found");
        }

        // Validate request against stored authData
        $authData = $this->storage->fetchAuthData($request->get('code'));
        if (!$authData) {
            Log::error("accessToken: code not found or expired");
            throw new BadRequestHttpException("code not found or expired");
        }

        if ($request->get('client_id') !== $authData['client_id']) {
            Log::error("accessToken: incorrect client id");
            throw new BadRequestHttpException("incorrect client id");
        }

        if ($request->get('redirect_uri') !== $authData['redirect_uri']) {
            Log::error("accessToken: incorrect redirect uri");
            throw new BadRequestHttpException("incorrect redirect uri");
        }

        // Verify challenge code (only support S256)
        if (! $this->verifyCodeChallenge($authData['code_challenge'], $request->get('code_verifier'))) {
            Log::error("accessToken: bad challenge");
            throw new BadRequestHttpException("bad challenge");
        }

        $jwt = $this->jwtService->generate($authData['hash']);

        // TODO: invalidate auth code after use?

        return new JsonResponse([
            'access_token' => $jwt,
            'expires_in' => 3600,
            'token_type' => 'bearer',
        ]);
    }

    protected function verifyCodeChallenge(string $targetChallenge, string $verifier): bool
    {
        $challenge = $this->base64url(hash('sha256', $verifier, true));

        return hash_equals($challenge, $targetChallenge);
    }

    protected function base64url(string $data): string
    {
        $data = base64_encode($data);
        $data = strtr($data, '+/', '-_');

        return rtrim($data, '=');
    }

    protected function generateAuthCode(): string
    {
        return hash('sha256', random_bytes(32));
    }
}
