<?php

declare(strict_types=1);

namespace App\Services\EmailGateway;

use App\Mail\SendCode;
use Illuminate\Support\Facades\Log;

class Dummy implements EmailGatewayInterface
{
    public function send(string $email, string $template, array $vars): bool
    {
        $email = new SendCode($vars['code']);
        Log::debug("Dummy e-mail: " . $email->render());

        return true;
    }
}
