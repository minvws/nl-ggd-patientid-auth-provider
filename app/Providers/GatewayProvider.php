<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\CmsService;
use App\Services\EmailGateway;
use App\Services\EmailService;
use App\Services\InfoRetrievalGateway;
use App\Services\InfoRetrievalService;
use App\Services\SmsGateway;
use App\Services\SmsService;
use Illuminate\Support\ServiceProvider;

class GatewayProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(SmsService::class, function () {
            switch (config('gateway.sms_service')) {
                case 'dummy':
                    return new SmsService(new SmsGateway\Dummy());
                case 'messagebird':
                    return new SmsService(
                        new SmsGateway\RateLimiter(
                            new SmsGateway\MessageBird(
                                config('messagebird.api_key'),
                                config('messagebird.sender'),
                            ),
                            config('messagebird.ratelimit')
                        ),
                    );
                default:
                    throw new \Exception("Invalid SMS_SERVICE '" . $provider . "'");
            }
        });

        $this->app->singleton(EmailService::class, function () {
            switch (config('gateway.email_service')) {
                case 'dummy':
                    return new EmailService(new EmailGateway\Dummy());
                case 'native':
                    return new EmailService(new EmailGateway\Native());
                default:
                    throw new \Exception("Invalid EMAIL_SERVICE '" . $provider . "'");
            }
        });

        $this->app->singleton(CmsService::class, function () {
            return new CmsService(
                config('cms.cert'),
                config('cms.chain'),
            );
        });

        $this->app->singleton(InfoRetrievalService::class, function () {
            switch (config('gateway.info_retrieval_service')) {
                case 'dummy':
                    return new InfoRetrievalService(
                        new InfoRetrievalGateway\Dummy(
                            config('codegenerator.hmac_key', '')
                        )
                    );
                case 'yenlo':
                    return new InfoRetrievalService(
                        new InfoRetrievalGateway\Yenlo(
                            $this->app->get(CmsService::class),
                            config('yenlo.client_id'),
                            config('yenlo.client_secret'),
                            config('yenlo.token_url'),
                            config('yenlo.userinfo_url'),
                        )
                    );
                default:
                    throw new \Exception("Invalid INFORETRIEVAL_SERVICE '" . $provider . "'");
            }
        });
    }
}
