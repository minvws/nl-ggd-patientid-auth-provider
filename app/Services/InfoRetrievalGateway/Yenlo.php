<?php

declare(strict_types=1);

namespace App\Services\InfoRetrievalGateway;

use App\Services\CmsService;
use App\Services\UserInfo;
use App\Exceptions\CmsValidationException;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Yenlo implements InfoRetrievalGateway
{
    protected const CACHE_KEY = 'yenlo_accesstoken';

    protected CmsService $cmsService;
    protected string $clientId;
    protected string $clientSecret;
    protected string $tokenUrl;
    protected string $userinfoUrl;

    public function __construct(
        CmsService $cmsService,
        string $clientId,
        string $clientSecret,
        string $tokenUrl,
        string $userinfoUrl,
    ) {
        $this->cmsService = $cmsService;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->tokenUrl = $tokenUrl;
        $this->userinfoUrl = $userinfoUrl;
    }

    /**
     * @throws \Exception
     */
    public function retrieve(string $userHash): UserInfo
    {
        $accessToken = $this->fetchAccessToken();

        $userInfo = new UserInfo();

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

            if (isset($data['email'])) {
                $userInfo->withEmail($data['email']);
            }
            if (isset($data['phoneNumber'])) {
                $userInfo->withPhoneNumber($data['phoneNumber']);
            }

        } catch (\Throwable $e) {
            // error
            Log::error("Error while receiving data from yenlo: " . $e->getMessage());
        }

        return $userInfo;
    }

    /*
     * Retrieves a new access token, or uses a cached one if available and not expired
     * @throws \Exception
     */
    protected function fetchAccessToken(): string
    {
        $token = Cache::get(self::CACHE_KEY);
        if (is_array($token)) {
            return $token['access_token'];
        }

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

        return $jwt['access_token'];
    }

    /**
     * @throws \JsonException
     * @throws CmsValidationException
     */
    protected function decodeAndVerifyResponse(string $body): array
    {
        $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        $signature = base64_decode($json['signature']);
        $payload = base64_decode($json['payload']);

        $this->cmsService->verify($payload, $signature);

        return json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
    }
}
