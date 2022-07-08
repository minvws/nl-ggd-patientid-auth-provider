<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\JwtService;
use Illuminate\Support\ServiceProvider;

class JwtProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(JwtService::class, function () {
            return new JwtService(
                config('jwt.private_key_path'),
                config('jwt.certificate_path'),
                config('jwt.iss'),
                config('jwt.aud'),
                (int)config('jwt.exp'),
            );
        });
    }
}
