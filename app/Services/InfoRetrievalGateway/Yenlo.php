<?php

declare(strict_types=1);

namespace App\Services\InfoRetrievalGateway;

use App\Services\UserInfo;
use App\Exceptions\CmsValidationException;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Exception;

class Yenlo implements InfoRetrievalGateway
{
    protected const CACHE_KEY = 'yenlo_accesstoken';

    protected string $clientId;
    protected string $clientSecret;
    protected string $tokenUrl;
    protected string $userinfoUrl;

    public function __construct(
        string $clientId,
        string $clientSecret,
        string $tokenUrl,
        string $userinfoUrl,
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->tokenUrl = $tokenUrl;
        $this->userinfoUrl = $userinfoUrl;
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
            Log::error("Error while receiving auth data from yenlo: " . $e->getMessage());
            return "";
        }

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

        $this->checkCmsSignature($payload, $signature);

        return json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws CmsValidationException
     */
    private function checkCmsSignature(string $payload, string $signature): void
    {

        $tmpFilePayload = tmpfile();
        $tmpFileSignature = tmpfile();

        if (!$tmpFilePayload || !$tmpFileSignature) {
            throw new CmsValidationException('Cannot create temp file on disk');
        }

        // Locate CMS public key
        $cmsCertPath = config('cms.cert');
        $cmsChainPath = config('cms.chain');

        // Init files
        $tmpFilePayloadPath = stream_get_meta_data($tmpFilePayload)['uri'];
        $tmpFileSignaturePath = stream_get_meta_data($tmpFileSignature)['uri'];

        // Place data in files
        file_put_contents($tmpFilePayloadPath, $payload);
        file_put_contents($tmpFileSignaturePath, $signature);

        $args = [
            'openssl', 'cms', '-verify', '-nointern', '-content', $tmpFilePayloadPath, '-inform', 'DER', '-binary',
            '-in', $tmpFileSignaturePath,
            '-CAfile', $cmsChainPath,
            '-certfile', $cmsCertPath,
            '-no-CAfile','-no-CApath',
            '-purpose', 'any'
        ];

        $process = new Process($args);

        try {
            $process->run();
        } catch (Exception $exception) {
            Log::error((string)$exception);
            throw new CmsValidationException('Signature invalid');
        }

        $errOutput = $process->getErrorOutput();
        if ($errOutput !== "") {
            Log::info($errOutput);
        }

        if ($process->getExitCode() !== 0) {
            throw new CmsValidationException('Signature does not match payload');
        }
    }
}
