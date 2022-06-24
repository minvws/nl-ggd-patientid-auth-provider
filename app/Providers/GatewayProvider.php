<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\CmsService;
use App\Services\EmailGateway\Native;
use App\Services\EmailService;
use App\Services\InfoRetrievalGateway\Dummy as InfoRetrievalGatewayDummy;
use App\Services\InfoRetrievalGateway\Yenlo;
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
                new MessageBird(
                    config('messagebird.api_key'),
                    config('messagebird.sender'),
                )
            );
        });

        $this->app->singleton(EmailService::class, function () {
            return new EmailService(new Native());
        });

        $this->app->singleton(Yenlo::class, function () {
            return new Yenlo(
                new CmsService(
                    config('cms.cert'),
                    config('cms.chain'),
                ),
                config('yenlo.client_id'),
                config('yenlo.client_secret'),
                config('yenlo.token_url'),
                config('yenlo.userinfo_url'),
            );
        });

        $this->app->singleton(InfoRetrievalService::class, function () {
            switch (env('INFORETRIEVAL_SERVICE', 'yenlo')) {
                case 'dummy' :
                    $provider = new InfoRetrievalGatewayDummy(config('codegenerator.hmac_key', ''));
                    break;
                case 'yenlo':
                    $provider = new InfoRetrievalService($this->app->get(Yenlo::class));
                    break;
                default:
                    throw new \Exception("please provide your info provider through INFORETRIEVAL_SERVICE");
            }

            return new InfoRetrievalService($provider);
        });
    }
}
