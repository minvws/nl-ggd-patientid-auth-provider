<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\EmailGateway\Dummy as EmailDummy;
use App\Services\EmailService;
use App\Services\SmsGateway\Dummy as SmsDummy;
use App\Services\SmsService;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class GatewayProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(SmsService::class, function () {
            return new SmsService(new SmsDummy());
        });

        $this->app->singleton(EmailService::class, function () {
            return new EmailService(new EmailDummy());
        });
    }
}
