<?php

declare(strict_types=1);

namespace App\Services\SmsGateway;

class Dummy implements SmsGatewayInterface
{
    public function send(string $phoneNr, string $template, array $vars): bool
    {
        return true;
    }
}