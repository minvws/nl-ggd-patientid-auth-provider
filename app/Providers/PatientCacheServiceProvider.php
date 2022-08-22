<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\PatientCodeGenerationThrottleService;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Cache;

class PatientCacheServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->bind(PatientCodeGenerationThrottleService::class, function () {
            return new PatientCodeGenerationThrottleService(Cache::store(config('codegenerator.throttle.cache_driver')));
        });
    }
}
