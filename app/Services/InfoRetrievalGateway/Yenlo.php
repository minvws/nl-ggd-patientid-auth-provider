<?php

declare(strict_types=1);

namespace App\Services\InfoRetrievalGateway;

use App\Exceptions\UserInfoRetrieveException;
use App\Services\UserInfo;
use App\Exceptions\CmsValidationException;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use MinVWS\Crypto\Laravel\SignatureCryptoInterface;

class Yenlo implements InfoRetrievalGateway
{
    protected const CACHE_KEY = 'yenlo_accesstoken';

    protected SignatureCryptoInterface $signatureService;
    protected string $clientId;
    protected string $clientSecret;
    protected string $tokenUrl;
    protected string $userinfoUrl;

    public function __construct(
        SignatureCryptoInterface $signatureService,
        string $clientId,
        string $clientSecret,
        string $tokenUrl,
        string $userinfoUrl,
    ) {
        $this->signatureService = $signatureService;
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
        try {
            $accessToken = $this->fetchAccessToken();

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

            $userInfo = new UserInfo($userHash);
            if (isset($data['email'])) {
                $userInfo->withEmail($data['email']);
            }
            if (isset($data['phoneNumber'])) {
                $userInfo->withPhoneNumber($data['phoneNumber']);
            }
        } catch (\Throwable $e) {
            Log::error("yenlo::retrieve: error while receiving or processing data: " . $e->getMessage());
            throw new UserInfoRetrieveException($e->getMessage());
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

        $body = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $validator = Validator::make($body, [
            'expires_in' => 'required|int',
            'access_token' => 'required|string',
        ]);
        if ($validator->fails()) {
            Log::error("yenlo::fetchAccessToken: error while validating body: " . json_encode($body));
            throw new UserInfoRetrieveException("Error fetching/parsing response from Yenlo access token request");
        }

        Cache::put(self::CACHE_KEY, $body, $body['expires_in'] - 10);

        return $body['access_token'];
    }

    /**
     * @throws \JsonException
     * @throws CmsValidationException
     */
    protected function decodeAndVerifyResponse(string $body): array
    {
        $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        $signature = $json['signature'];
        $payload = base64_decode($json['payload']);

        if (!$this->signatureService->verify($signature, $payload, file_get_contents(config('cms.cert')) ?: '')) {
            throw new CmsValidationException('Signature does not match payload');
        }

        return json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
    }
}
