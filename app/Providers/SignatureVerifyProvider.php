<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use MinVWS\Crypto\Laravel\Service\Signature\ProcessSpawnService;
use MinVWS\Crypto\Laravel\Service\Signature\NativeService;
use MinVWS\Crypto\Laravel\Service\TempFileService;
use MinVWS\Crypto\Laravel\SignatureVerifyCryptoInterface;
use MinVWS\Crypto\Laravel\TempFileInterface;

class SignatureVerifyProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->bind(TempFileInterface::class, TempFileService::class);

        $this->app->bind(SignatureVerifyCryptoInterface::class, function () {
            if (config('cms.verify_service') === 'process_spawn') {
                return new ProcessSpawnService(
                    certPath: null,
                    privKeyPath: null,
                    privKeyPass: null,
                    certChainPath: config('cms.chain'),
                    tempFileService: app(TempFileInterface::class),
                );
            }

            return new NativeService(
                certPath: null,
                privKeyPath: null,
                privKeyPass: null,
                certChainPath: config('cms.chain'),
                tempFileService: app(TempFileInterface::class),
            );
        });
    }
}
