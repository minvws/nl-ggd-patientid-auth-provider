<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class ContactInfoNotFound extends Exception
{
    public function report(): void
    {
        \Log::debug('Contact info not found');
    }
}
