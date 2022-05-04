<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\CodeGeneratorService;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class CodeGeneratorProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(CodeGeneratorService::class, function () {
            return new CodeGeneratorService(config('codegenerator.hmac_key'), config('codegenerator.expiry'));
        });
    }
}
