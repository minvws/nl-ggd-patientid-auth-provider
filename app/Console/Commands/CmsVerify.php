<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Exception;
use MinVWS\Crypto\Laravel\SignatureCryptoInterface;
use Illuminate\Support\Facades\Log;

class CmsVerify extends Command
{
    protected $signature = 'cms:verify {json}';
    protected $description = 'Verify the signature on a signed JSON message';

    public function handle(SignatureCryptoInterface $signatureService): int
    {
        $json = $this->argument("json");
        if (!is_string($json)) {
            throw new Exception("Invalid JSON");
        }

        $message = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $signature = $message['signature'];
        $payload = base64_decode($message['payload']);

        if (!$signatureService->verify($signature, $payload, file_get_contents(config('cms.cert')) ?: '')) {
            $this->line("Verification FAILED");
            return 1;
        }

        $this->line("Verification successful");
        return 1;
    }
}
