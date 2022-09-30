<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\EmailGateway;
use App\Services\EmailService;
use App\Services\InfoRetrievalGateway;
use App\Services\InfoRetrievalService;
use App\Services\SmsGateway;
use App\Services\SmsService;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\ServiceProvider;
use MinVWS\Crypto\Laravel\Factory;

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
            $signatureService = Factory::createSignatureCryptoService(
                certificateChain: config('cms.chain'),
                forceProcessSpawn: config('cms.verify_service', "native") === "process_spawn",
            );

            $client = new Client([
                RequestOptions::AUTH => [
                    config('yenlo.client_id'),
                    config('yenlo.client_secret'),
                ],
            ]);

            $provider = config('gateway.info_retrieval_service');
            return match ($provider) {
                'dummy' => new InfoRetrievalService(
                    new InfoRetrievalGateway\Dummy(
                        config('codegenerator.hmac_key', '')
                    )
                ),
                'yenlo' => new InfoRetrievalService(
                    new InfoRetrievalGateway\Yenlo(
                        $signatureService,
                        $client,
                        config('yenlo.token_url'),
                        config('yenlo.userinfo_url'),
                        config('cms.cert')
                    )
                ),
                default => throw new \Exception("Invalid INFORETRIEVAL_SERVICE '" . $provider . "'"),
            };
        });
    }
}
