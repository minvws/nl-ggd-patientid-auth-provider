<?php

declare(strict_types=1);

namespace App\Services\InfoRetrievalGateway;

use App\Services\JwtService;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Cache;

class Yenlo implements InfoRetrievalGateway
{
    protected const CACHE_KEY = 'yenlo_accesstoken';

    protected JwtService $jwtService;
    protected string $clientId;
    protected string $clientSecret;
    protected string $tokenUrl;
    protected string $userinfoUrl;

    public function __construct(
        string $clientId,
        string $clientSecret,
        string $tokenUrl,
        string $userinfoUrl
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->tokenUrl = $tokenUrl;
        $this->userinfoUrl = $userinfoUrl;
    }

    public function retrieve(string $userHash): array
    {
        $accessToken = $this->fetchAccessToken();

        try {
            $client = new Client([
                'http_errors' => false,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $accessToken,
                    'CoronaCheck-Protocol-Version' => '3.0',
                ],
            ]);

            $response = $client->post($this->userinfoUrl, [
                RequestOptions::JSON => [
                    'userhash' => $userHash,
                ]
            ]);

            // @TODO: Retrieved info.. do something with it
            print_r($response->getStatusCode());
            var_dump((string)$response->getBody());
        } catch (\Throwable $e) {
            // error
            var_dump($e->getMessage());
        }

        return [];
    }

    // Retrieves a new access token, or uses a cached one if available and not expired
    protected function fetchAccessToken(): string
    {
        $token = Cache::get(self::CACHE_KEY);
        if (is_array($token)) {
            return $token['access_token'];
        }

        try {
            $client = new Client([
                'http_errors' => false,
                'auth' => [
                    $this->clientId,
                    $this->clientSecret,
                ]
            ]);

            $response = $client->post($this->userinfoUrl, [
                RequestOptions::FORM_PARAMS => [
                    'grant_type' => 'client_credentials',
                ]
            ]);

            $jwt = json_decode((string)$response->getBody(), true);
            Cache::put(self::CACHE_KEY, $jwt, $jwt['expires_in'] - 10);
        } catch (\Throwable $e) {
            return "";
        }

        return $jwt['access_token'];
    }
}
