<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\EmailGateway;
use App\Services\EmailService;
use App\Services\InfoRetrievalGateway;
use App\Services\InfoRetrievalService;
use App\Services\SmsGateway;
use App\Services\SmsService;
use Illuminate\Support\ServiceProvider;
use MinVWS\Crypto\Laravel\SignatureVerifyCryptoInterface;

class GatewayProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(SmsService::class, function () {
            $provider = config('gateway.sms_service');
            return match ($provider) {
                'dummy' => new SmsService(new SmsGateway\Dummy()),
                'messagebird' => new SmsService(
                    new SmsGateway\RateLimiter(
                        new SmsGateway\MessageBird(
                            config('messagebird.api_key'),
                            config('messagebird.sender'),
                        ),
                        config('messagebird.ratelimit')
                    ),
                ),
                default => throw new \Exception("Invalid SMS_SERVICE '" . $provider . "'"),
            };
        });

        $this->app->singleton(EmailService::class, function () {
            $provider = config('gateway.email_service');
            return match ($provider) {
                'dummy' => new EmailService(new EmailGateway\Dummy()),
                'native' => new EmailService(new EmailGateway\Native()),
                default => throw new \Exception("Invalid EMAIL_SERVICE '" . $provider . "'"),
            };
        });

        $this->app->singleton(InfoRetrievalService::class, function () {
            $provider = config('gateway.info_retrieval_service');
            return match ($provider) {
                'dummy' => new InfoRetrievalService(
                    new InfoRetrievalGateway\Dummy(
                        config('codegenerator.hmac_key', '')
                    )
                ),
                'yenlo' => new InfoRetrievalService(
                    new InfoRetrievalGateway\Yenlo(
                        $this->app->get(SignatureVerifyCryptoInterface::class),
                        config('yenlo.client_id'),
                        config('yenlo.client_secret'),
                        config('yenlo.token_url'),
                        config('yenlo.userinfo_url'),
                    )
                ),
                default => throw new \Exception("Invalid INFORETRIEVAL_SERVICE '" . $provider . "'"),
            };
        });
    }
}
