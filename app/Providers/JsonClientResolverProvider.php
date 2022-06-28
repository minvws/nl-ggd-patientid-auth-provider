<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Oidc\JsonClientResolver;
use Illuminate\Support\ServiceProvider;

class JsonClientResolverProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(JsonClientResolver::class, function () {
            return new JsonClientResolver(base_path(config('oidc.client_config_path')));
        });
    }
}
