<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\ResendThrottleRetryAfterException;
use App\Services\ResendThrottleService;
use Closure;
use Illuminate\Http\Request;

class ResendThrottleMiddleware
{
    public function __construct(
        protected ResendThrottleService $throttleService,
    ) {
    }

    /**
     * @throws ResendThrottleRetryAfterException
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $patientHash = $request->patientHash();

        $retryAfter = $this->throttleService->getRetryAfter($patientHash);
        if ($retryAfter !== null) {
            throw new ResendThrottleRetryAfterException($retryAfter);
        }

        $this->throttleService->attempt($patientHash);
        return $next($request);
    }
}
