<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Oidc\ClientResolverInterface;
use App\Services\Oidc\StorageInterface;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $params = $this->getAuthParams($request->all());

        // Start or replace session
        $request->session()->flush();
        foreach ($params as $key => $value) {
            $request->session()->put($key, $value);
        }

        return Redirect::route('login');
    }

    public function finishAuthorize(Request $request, string $hash): RedirectResponse
    {
        $params = $this->getAuthParams($request->session()->all());
        $authData = array_merge($params, ['hash' => $hash]);

        // Create authentication code and cache the request vars for when the auth code is used by the client
        $authCode = $this->generateAuthCode();
        $this->storage->saveAuthData($authCode, $authData);

        // Clear current session
        $request->session()->flush();

        $qs = http_build_query([
            'state' => $request->get('state'),
            'code' => $authCode,
        ]);
        $redirectUri = $authData['redirect_uri'] . '?' . $qs;

        \Log::debug($redirectUri);

        // TODO: reset auth code expiry?

        return new RedirectResponse($redirectUri);
    }

    public function hasAuthorizeSession(Request $request): bool
    {
        try {
            $this->getAuthParams($request->session()->all());
            return true;
        } catch (\Throwable $e) {
            $request->session()->flush();
            return false;
        }
    }

    public function getAuthParams(array $params): array
    {
        $validator = Validator::make($params, [
            'response_type' => ['required', 'string'],
            'client_id' => ['required', 'string'],
            'state' => ['required', 'string'],
            'scope' => ['required', 'string'],
            'redirect_uri' => ['required', 'string'],
            'code_challenge' => ['required', 'string'],
            'code_challenge_method' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            throw new BadRequestHttpException("incomplete set of request data found");
        }

        if ($params['response_type'] != "code") {
            throw new BadRequestHttpException("code expected as response type");
        }

        if (empty($params['code_challenge'])) {
            throw new BadRequestHttpException("code challenge expected");
        }

        if ($params['code_challenge_method'] != "S256") {
            throw new BadRequestHttpException("incorrect hashing method");
        }

        if (! $this->clientResolver->exists($params['client_id'])) {
            throw new BadRequestHttpException("incorrect client id");
        }

        // Check if the client-id matches something we can accept, and check if
        // the redirect_uri is valid for the client_id
        $client = $this->clientResolver->resolve($params['client_id']);
        if (!$client || !in_array($params['redirect_uri'], $client->getRedirectUris())) {
            throw new BadRequestHttpException("invalid redirect uri specified");
        }

        return $validator->validated();
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
            throw new BadRequestHttpException("incomplete set of request data found");
        }

        // Check params
        if ($request->get('grant_type') != "authorization_code") {
            throw new BadRequestHttpException("authorization_code expected as response type");
        }

        if (empty($request->get('code'))) {
            throw new BadRequestHttpException("code not found");
        }

        // Validate request against stored authData
        $authData = $this->storage->fetchAuthData($request->get('code'));
        if (!$authData) {
            throw new BadRequestHttpException("code not found or expired");
        }

        if ($request->get('client_id') != $authData['client_id']) {
            throw new BadRequestHttpException("incorrect client id");
        }

        if ($request->get('redirect_uri') != $authData['redirect_uri']) {
            throw new BadRequestHttpException("incorrect redirect uri");
        }

        // Verify challenge code (only support S256)
        $codeChallenge = $this->base64url(hash('sha256', $request->get('code_verifier'), true));
        if (! hash_equals($codeChallenge, $authData['code_challenge'])) {
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

    protected function base64url(string $data): string
    {
        $data = base64_encode($data);
        $data = strtr($data, '+/', '-_');

        return rtrim($data, '=');
    }

    protected function verifyCodeChallenge(string $challenge, string $verifier): bool
    {
        return $challenge == rtrim(strtr(base64_encode($verifier), '+/', '-_'), '=');
    }

    protected function generateAuthCode(): string
    {
        return hash('sha256', uniqid('', true));
    }
}
