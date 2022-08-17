<?php

namespace App\Limiter;

use Illuminate\Cache\RateLimiter;

class ResendRateLimiter extends RateLimiter
{
    public function hit($key, $decaySeconds = 60): int
    {
        if (
            $this->attempts($key) === 0
            || $this->attempts($key) === 1
            || $this->attempts($key) === 2
        ) {
            return parent::hit($key, $decaySeconds * 5);
        }
        if ($this->attempts($key) === 3) {
            return parent::hit($key, $decaySeconds * 15);
        }
        if ($this->attempts($key) === 4) {
            return parent::hit($key, $decaySeconds * 30);
        }

        return parent::hit($key, $decaySeconds * 60);
    }
}
