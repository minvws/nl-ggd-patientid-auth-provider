<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use OpenSSLAsymmetricKey;

class WellKnownController extends Controller
{
    public function configuration(): JsonResponse
    {
        if (Cache::has('configuration')) {
            return response()->json(Cache::get('configuration'));
        }

        $jsonData = [
            'version' => '3.0',
            'token_endpoint_auth_methods_supported' => [
                'none',
            ],
            'claims_parameter_supported' => true,
            'request_parameter_supported' => false,
            'request_uri_parameter_supported' => true,
            'require_request_uri_registration' => false,
            'grant_types_supported' => [
                'authorization_code',
            ],
            'frontchannel_logout_supported' => false,
            'frontchannel_logout_session_supported' => false,
            'backchannel_logout_supported' => false,
            'backchannel_logout_session_supported' => false,
            'issuer' => url('/'),
            'authorization_endpoint' => url('/oidc/authorize'),
            'token_endpoint' => url('/oidc/accesstoken'),
            'jwks_uri' => url('/.well-known/jwks.json'),
            'scopes_supported' => [
                'openid',
            ],
            'response_types_supported' => [
                'code',
            ],
            'response_modes_supported' => [
                'query',
            ],
            'subject_types_supported' => [
                'pairwise',
            ],
            'id_token_signing_alg_values_supported' => [
                'RS256',
            ],
        ];

        Cache::put('configuration', $jsonData, now()->addMinutes(5));

        return response()->json($jsonData);
    }

    public function jwks(): JsonResponse
    {
        if (Cache::has('jwks')) {
            return response()->json(Cache::get('jwks'));
        }

        /** @var string $certificate */
        $certificate = file_get_contents(base_path(config('jwt.certificate_path')));
        /** @var OpenSSLAsymmetricKey $publicKey */
        $publicKey = openssl_pkey_get_public($certificate);
        /** @var array $keyInfo */
        $keyInfo = openssl_pkey_get_details($publicKey);

        $jsonData = [
            'keys' => [
                [
                    "kid" => hash('sha256', $certificate),
                    'kty' => 'RSA',
                    'n' => rtrim(str_replace(['+', '/'], ['-', '_'], base64_encode($keyInfo['rsa']['n'])), '='),
                    'e' => rtrim(str_replace(['+', '/'], ['-', '_'], base64_encode($keyInfo['rsa']['e'])), '='),
                ],
            ],
        ];

        Cache::put('jwks', $jsonData, now()->addMinutes(5));

        return response()->json($jsonData);
    }
}
