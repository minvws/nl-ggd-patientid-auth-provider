<?php

declare(strict_types=1);

namespace App\Services\InfoRetrievalGateway;

use App\Services\UserInfo;
use App\Services\JwtService;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use MinVWS\Crypto\Laravel\SignatureCryptoInterface;

class Yenlo implements InfoRetrievalGateway
{
    protected const CACHE_KEY = 'yenlo_accesstoken';

    protected JwtService $jwtService;
    protected string $clientId;
    protected string $clientSecret;
    protected string $tokenUrl;
    protected string $userinfoUrl;
    protected SignatureCryptoInterface $signatureService;

    public function __construct(
        string $clientId,
        string $clientSecret,
        string $tokenUrl,
        string $userinfoUrl,
        SignatureCryptoInterface $signatureService
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->tokenUrl = $tokenUrl;
        $this->userinfoUrl = $userinfoUrl;
        $this->signatureService = $signatureService;
    }

    public function retrieve(string $userHash): UserInfo
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

            $data = $this->decodeAndVerifyResponse((string)$response->getBody());

            $info = new UserInfo();
            if (isset($data['email'])) {
                $info->withEmail($data['email']);
            }
            if (isset($data['phoneNr'])) {
                $info->withPhonenr($data['phoneNr']);
            }

            return $info;
        } catch (\Throwable $e) {
            // error
            Log::error("Error while receiving data from yenlo: " . $e->getMessage());
        }

        return new UserInfo();
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

            $response = $client->post($this->tokenUrl, [
                RequestOptions::FORM_PARAMS => [
                    'grant_type' => 'client_credentials',
                ]
            ]);

            $jwt = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            Cache::put(self::CACHE_KEY, $jwt, $jwt['expires_in'] - 10);
        } catch (\Throwable $e) {
            return "";
        }

        return $jwt['access_token'];
    }

    protected function decodeAndVerifyResponse(string $body): array
    {
        $json = json_decode($body, true, JSON_THROW_ON_ERROR);

        $verified = $this->signatureService->verify($json['payload']);
        if (!$verified) {
            return [];
        }

        return json_decode($json['payload'], false, JSON_THROW_ON_ERROR);
    }
}
