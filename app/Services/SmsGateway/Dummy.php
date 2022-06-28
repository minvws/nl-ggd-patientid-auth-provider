<?php

declare(strict_types=1);

namespace App\Services\SmsGateway;

use Illuminate\Support\Facades\Log;

class Dummy implements SmsGatewayInterface
{
    public function send(string $phoneNumber, string $template, array $vars): bool
    {
        Log::debug("Dummy SMS: " . $vars['code']);
        return true;
    }
}
