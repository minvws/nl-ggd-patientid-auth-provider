<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\CmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Exception;

class CmsVerify extends Command
{
    protected $signature = 'cms:verify {json}';
    protected $description = 'Verify the signature on a signed JSON message';

    public function handle(): int
    {
        $json = $this->argument("json");
        if (!is_string($json)) {
            throw new Exception("Invalid JSON");
        }
        $cmsService = new CmsService(
            config('cms.cert'),
            config('cms.chain'),
        );
        $message = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $signature = base64_decode($message['signature']);
        $payload = base64_decode($message['payload']);
        try {
            $cmsService->verify($payload, $signature);
            $this->line("Verification successful");
        } catch (Exception $e) {
            $this->line("Verification FAILED");
            Log::error((string)$e);
        }
        return 1;
    }
}
