<?php

namespace App\Http\Middleware;

use App\Limiter\ResendRateLimiter;
use Closure;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpFoundation\Response;

class ResendThrottleMiddleware extends ThrottleRequests
{
    public function __construct(ResendRateLimiter $limiter)
    {
        parent::__construct($limiter);
    }

    /**
     * Throttle by session ID
     * @param $request
     * @return string
     */
    protected function resolveRequestSignature($request): string
    {
        return $request->session()->getId();
    }

    public function handle($request, Closure $next, $maxAttempts = 29, $decayMinutes = 1, $prefix = ''): Response
    {
        return parent::handle($request, $next, $maxAttempts, $decayMinutes, $prefix);
    }
}
