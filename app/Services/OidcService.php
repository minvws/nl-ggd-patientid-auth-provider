<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Oidc\ClientResolverInterface;
use App\Services\Oidc\StorageInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class OidcService
{
    protected ClientResolverInterface $clientResolver;
    protected StorageInterface $storage;

    public function __construct(ClientResolverInterface $clientResolver, StorageInterface $storage)
    {
        $this->clientResolver = $clientResolver;
        $this->storage = $storage;
    }

    public function tokenExists(string $accessToken): bool
    {
        return $this->storage->accessTokenExists($accessToken);
    }

    public function generateToken(): string
    {
        $token = $this->generateAccessToken();
        $this->storage->saveAccessToken($token);

        return $token;
    }

    public function authorize(Request $request): RedirectResponse
    {
        // Validate request for needed parameters
        $validator = Validator::make($request->all(), [
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

        if ($request->get('response_type') != "code") {
            throw new BadRequestHttpException("code expected as response type");
        }
        if (empty($request->get('code_challenge'))) {
            throw new BadRequestHttpException("code challenge expected");
        }
        if ($request->get('code_challenge_method') != "S256") {
            throw new BadRequestHttpException("incorrect hashing method");
        }

        if (! $this->clientResolver->exists($request->get('client_id'))) {
            throw new BadRequestHttpException("incorrect client id");
        }

        // Check if the client-id matches something we can accept, and check if
        // the redirect_uri is valid for the client_id
        $client = $this->clientResolver->resolve($request->get('client_id'));
        if (!$client || !in_array($request->get('redirect_uri'), $client->getRedirectUris())) {
            throw new BadRequestHttpException("invalid redirect uri specified");
        }

        // Create authentication code and cache the request vars for when the auth code is used by the client
        $authCode = $this->generateAuthCode();
        $this->storage->saveAuthData($authCode, $request->all());

        // Generate redirect url with query string
        $qs = http_build_query([
            'state' => $request->get('state'),
            'code' => $authCode,
        ]);

        return new RedirectResponse($request->get('redirect_uri') . '?' . $qs);
    }

    public function accessToken(Request $request): JsonResponse
    {
        // Validate request for needed parameters
        $validator = Validator::make($request->all(), [
            'grant_type' => ['required', 'string'],
            'code' => ['required', 'string'],
            'redirect_uri' => ['required', 'string'],
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

        // Fetch info from the storage for this code
        $ccInfo = $this->storage->fetchAuthData($request->get('code'));
        if (!$ccInfo) {
            throw new BadRequestHttpException("code not found or expired");
        }

        if ($request->get('client_id') != $ccInfo['client_id']) {
            throw new BadRequestHttpException("incorrect client id");
        }
        if ($request->get('redirect_uri') != $ccInfo['redirect_uri']) {
            throw new BadRequestHttpException("incorrect redirect uri");
        }

        // Verify challenge code (only support S256)
        $codeChallenge = $this->base64url(hash('sha256', $request->get('code_verifier'), true));
        if (! hash_equals($codeChallenge, $ccInfo['code_challenge'])) {
            throw new BadRequestHttpException("bad challenge");
        }

        // Generate access token and store in cache
        $accessToken = $this->generateAccessToken();
        $this->storage->saveAccessToken($accessToken);

        return new JsonResponse([
            'access_token' => $accessToken,
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

    protected function generateAccessToken(): string
    {
        return hash('sha256', random_bytes(32));
    }

    protected function generateAuthCode(): string
    {
        return hash('sha256', random_bytes(32));
    }

    public function fetchTokenFromRequest(Request $request): ?string
    {
        // Check authorization header first
        $authHeader = strval($request->header('authorization', ''));
        if (str_starts_with($authHeader, "bearer ")) {
            return (string)str_replace("bearer ", "", $authHeader);
        }

        // Check query string or post
        $token = $request->query->getAlnum('access_token', '');
        if (! empty($token)) {
            return $token;
        }

        // check post
        $token = $request->request->getAlnum('access_token');
        return ! empty($token) ? $token : null;
    }
}
