<?php

declare(strict_types=1);

namespace App\Services\EmailGateway;

interface EmailGatewayInterface
{
    public function send(string $email, string $template, array $vars): bool;
}
