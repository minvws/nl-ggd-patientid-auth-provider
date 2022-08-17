<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Carbon;

class ResendThrottleRetryAfterException extends Exception
{
    public function __construct(protected Carbon $retryAfter)
    {
        parent::__construct();
    }

    public function getRetryAfter(): Carbon
    {
        return $this->retryAfter;
    }
}
