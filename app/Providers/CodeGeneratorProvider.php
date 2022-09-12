<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\CodeGeneratorService;
use App\Services\PatientCodeGenerationThrottleService;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Cache;

class CodeGeneratorProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(CodeGeneratorService::class, function () {
            return new CodeGeneratorService(config('codegenerator.hmac_key'), config('codegenerator.expiry'));
        });

        $this->bind(PatientCodeGenerationThrottleService::class, function () {
            return new PatientCodeGenerationThrottleService(
                Cache::store(config('codegenerator.throttle.cache_store'))
            );
        });
    }
}
