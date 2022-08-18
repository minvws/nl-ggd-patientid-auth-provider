<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\ResendThrottleService;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Cache;

class ResendThrottleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->bind(ResendThrottleService::class, function () {
            return new ResendThrottleService(Cache::store(config('throttle.resend.cache_driver')));
        });
    }
}
