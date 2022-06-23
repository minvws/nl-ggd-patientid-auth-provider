<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\SmsGateway\SmsGatewayInterface;

class SmsService
{
    protected SmsGatewayInterface $gateway;

    public function __construct(SmsGatewayInterface $gateway)
    {
        $this->gateway = $gateway;
    }

    public function send(string $phoneNumber, string $template, array $vars): bool
    {
        return $this->gateway->send($phoneNumber, $template, $vars);
    }
}
