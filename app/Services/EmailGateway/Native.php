<?php

declare(strict_types=1);

namespace App\Services\EmailGateway;

use App\Mail\SendCode;
use Illuminate\Support\Facades\Mail;

class Native implements EmailGatewayInterface
{
    public function send(string $email, string $template, array $vars): bool
    {
        return Mail::to($email)->send(new SendCode($vars['code'])) !== null;
    }
}
