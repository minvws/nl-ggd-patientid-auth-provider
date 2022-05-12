<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\EmailGateway\Native;
use App\Services\InfoRetrievalGateway\Dummy as InfoRetrievalGatewayDummy;
use App\Services\EmailService;
use App\Services\InfoRetrievalService;
use App\Services\SmsGateway\MessageBird;
use App\Services\SmsService;
use Illuminate\Support\ServiceProvider;

class GatewayProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(SmsService::class, function () {
            return new SmsService(
                new MessageBird(config('messagebird.api_key'))
            );
        });

        $this->app->singleton(EmailService::class, function () {
            return new EmailService(new Native());
        });

        $this->app->singleton(InfoRetrievalService::class, function () {
            return new InfoRetrievalService(new InfoRetrievalGatewayDummy());
        });
    }
}
