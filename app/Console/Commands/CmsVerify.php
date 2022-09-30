<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Exception;
use MinVWS\Crypto\Laravel\Factory;
use MinVWS\Crypto\Laravel\Service\Signature\SignatureVerifyConfig;

class CmsVerify extends Command
{
    protected $signature = 'cms:verify {json}';
    protected $description = 'Verify the signature on a signed JSON message';

    public function handle(): int
    {
        $signatureService = Factory::createSignatureCryptoService(
            certificateChain: config('cms.chain'),
            forceProcessSpawn: config('cms.verify_service', "native") === "process_spawn",
        );

        $json = $this->argument("json");
        if (!is_string($json)) {
            throw new Exception("Invalid JSON");
        }

        $message = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $signature = $message['signature'];
        $payload = base64_decode($message['payload']);

        $cert = file_get_contents(config('cms.cert')) ?: '';
        if (!$signatureService->verify($signature, $payload, $cert, $this->getSignatureVerifyConfig())) {
            $this->line("Verification FAILED");
            return 1;
        }

        $this->line("Verification successful");
        return 1;
    }

    public function getSignatureVerifyConfig(): SignatureVerifyConfig
    {
        return (new SignatureVerifyConfig())->setBinary(true);
    }
}
