<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\CmsValidationException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Exception;

class CmsService
{
    protected string $cmsCertPath;
    protected string $cmsChainPath;

    public function __construct(string $cmsCertPath, string $cmsChainPath)
    {
        $this->cmsCertPath = $cmsCertPath;
        $this->cmsChainPath = $cmsChainPath;
    }

    /**
     * @throws CmsValidationException
     */
    public function verify(string $payload, string $signature): void
    {
        $tmpFilePayload = tmpfile();
        $tmpFileSignature = tmpfile();

        if (!$tmpFilePayload || !$tmpFileSignature) {
            Log::warning("verify: cannot create temp file on disk");
            throw new CmsValidationException('Cannot create temp file on disk');
        }

        // Init files
        $tmpFilePayloadPath = stream_get_meta_data($tmpFilePayload)['uri'];
        $tmpFileSignaturePath = stream_get_meta_data($tmpFileSignature)['uri'];

        // Place data in files
        file_put_contents($tmpFilePayloadPath, $payload);
        file_put_contents($tmpFileSignaturePath, $signature);

        $args = [
            'openssl', 'cms', '-verify', '-nointern', '-content', $tmpFilePayloadPath, '-inform', 'DER', '-binary',
            '-in', $tmpFileSignaturePath,
            '-certfile', $this->cmsCertPath,
            '-CAfile', $this->cmsChainPath,
            '-no-CAfile','-no-CApath',
            '-purpose', 'any'
        ];

        try {
            $process = new Process($args);
            $process->run();
        } catch (Exception $e) {
            Log::error("verify: openssl process: " . $e->getMessage());
            throw new CmsValidationException('Signature invalid');
        }

        $errOutput = $process->getErrorOutput();
        if ($errOutput !== "") {
            Log::info("verify: openssl error output: " . $errOutput);
        }

        if ($process->getExitCode() !== 0) {
            Log::error("verify: openssl exit status: " . $process->getExitCode());
            throw new CmsValidationException('Signature does not match payload');
        }
    }
}
