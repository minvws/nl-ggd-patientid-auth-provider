<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\PatientCacheService;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Cache;

class PatientCacheServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->bind(PatientCacheService::class, function () {
            return new PatientCacheService(
                Cache::store(config('patient.cache_store'))
            );
        });
    }
}
