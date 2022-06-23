<?php

declare(strict_types=1);

namespace App\Services\SmsGateway;

interface SmsGatewayInterface
{
    public function send(string $phoneNumber, string $template, array $vars): bool;
}
