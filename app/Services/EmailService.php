<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\EmailGateway\EmailGatewayInterface;

class EmailService
{
    protected EmailGatewayInterface $gateway;

    public function __construct(EmailGatewayInterface $gateway)
    {
        $this->gateway = $gateway;
    }

    public function send(string $emailAddr, string $template, array $vars): bool
    {
        return $this->gateway->send($emailAddr, $template, $vars);
    }
}
