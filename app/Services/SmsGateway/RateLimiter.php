<?php

declare(strict_types=1);

namespace App\Services\SmsGateway;

use Illuminate\Support\Facades\RateLimiter as LaraLimiter;

class RateLimiter implements SmsGatewayInterface
{
    protected SmsGatewayInterface $provider;
    protected int $maxPerMinute;

    public function __construct(SmsGatewayInterface $provider, int $maxPerMinute)
    {
        $this->provider = $provider;
        $this->maxPerMinute = $maxPerMinute;
    }

    public function send(string $phoneNumber, string $template, array $vars): bool
    {
        $result = true;

        return LaraLimiter::attempt(
            'send-message:' . $phoneNumber,
            $this->maxPerMinute,
            function () use (&$result, $phoneNumber, $template, $vars) {
                $result = $this->provider->send($phoneNumber, $template, $vars);
            }
        ) && $result;
    }
}
