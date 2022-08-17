<?php

namespace App\Http\Middleware;

use App\Exceptions\ResendThrottleMaxAttemptsException;
use App\Exceptions\ResendThrottleRetryAfterException;
use Closure;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ResendThrottleMiddleware
{
    /**
     * @throws ResendThrottleRetryAfterException
     * @throws ResendThrottleMaxAttemptsException
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $session = $request->session();

        $retryAfter = $this->getRetryAfter($session);
        if ($retryAfter !== null) {
            throw new ResendThrottleRetryAfterException($retryAfter);
        }

        $attempts = $this->getAttempts($session);
        if ($attempts >= 28) {
            throw new ResendThrottleMaxAttemptsException();
        }

        $request->session()->put('resend_throttle:retry_after', $this->getNewRetryAfter($attempts));
        $request->session()->put('resend_throttle:attempts', ++$attempts);

        return $next($request);
    }

    public function getAttempts(Session $session): int
    {
        return $session->get('resend_throttle:attempts', 0);
    }

    public function getNewRetryAfter(int $attempt): string
    {
        $current = Carbon::now();

        $retryAfter = match ($attempt) {
            0, 1, 2 => $current->addMinutes(5),
            3 => $current->addMinutes(15),
            4 => $current->addMinutes(30),
            default => $current->addMinutes(60),
        };

        return $retryAfter->getTimestamp();
    }

    public function getRetryAfter(Session $session): ?Carbon
    {
        $retryAfter = $session->get('resend_throttle:retry_after');
        if ($retryAfter === null) {
            return null;
        }

        $retryAfter = Carbon::createFromTimestamp($retryAfter);
        if (Carbon::now()->gte($retryAfter)) {
            $this->clearRetryAfter($session);
            return null;
        }

        return $retryAfter;
    }

    public function clearRetryAfter(Session $session): void
    {
        $session->forget('resend_throttle:retry_after');
    }
}
