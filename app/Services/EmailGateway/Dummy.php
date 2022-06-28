<?php

declare(strict_types=1);

namespace App\Services\EmailGateway;

use Illuminate\Support\Facades\Log;

class Dummy implements EmailGatewayInterface
{
    public function send(string $email, string $template, array $vars): bool
    {
        Log::debug("Dummy e-mail: " . $vars['code']);
        return true;
    }
}
