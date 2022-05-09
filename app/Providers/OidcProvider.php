<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\EmailGateway\Dummy as EmailDummy;
use App\Services\InfoRetrievalGateway\Dummy as InfoRetrievalGatewayDummy;
use App\Services\EmailService;
use App\Services\InfoRetrievalService;
use App\Services\Oidc\CacheStorage;
use App\Services\Oidc\JsonClientResolver;
use App\Services\OidcService;
use App\Services\SmsGateway\MessageBird;
use App\Services\SmsService;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class OidcProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(OidcService::class, function () {
            return new OidcService(
                new JsonClientResolver(base_path(config('oidc.client_config_path'))),
                new CacheStorage()
            );
        });
    }
}
