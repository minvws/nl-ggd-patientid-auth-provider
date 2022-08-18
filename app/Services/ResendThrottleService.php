<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Carbon;

class ResendThrottleService
{
    protected const CACHE_KEY_RETRY_AFTER = 'resend_throttle.resend_after.';
    protected const CACHE_KEY_ATTEMPTS = 'resend_throttle.attempts.';
    protected const CACHE_KEY_LAST_ATTEMPT = 'resend_throttle.last_attempt.';

    public function __construct(
        protected Repository $cache,
    ) {
    }

    public function attempt(string $patientHash): void
    {
        $attempts = $this->getAttempts($patientHash);

        $this->cache->put(self::CACHE_KEY_RETRY_AFTER . $patientHash, $this->getNewRetryAfter($attempts));
        $this->cache->put(self::CACHE_KEY_ATTEMPTS . $patientHash, ++$attempts);
        $this->cache->put(self::CACHE_KEY_LAST_ATTEMPT . $patientHash, time());
    }

    public function getAttempts(string $patientHash): int
    {
        return $this->cache->get(self::CACHE_KEY_ATTEMPTS . $patientHash, 0);
    }

    public function getRetryAfter(string $patientHash): ?int
    {
        $retryAfter = $this->cache->get(self::CACHE_KEY_RETRY_AFTER . $patientHash);
        if ($retryAfter === null) {
            return null;
        }

        if (time() >= $retryAfter) {
            $this->clearRetryAfter($patientHash);
            return null;
        }

        return $retryAfter;
    }

    public function reset(string $patientHash): void
    {
        $this->clearRetryAfter($patientHash);
        $this->cache->forget(self::CACHE_KEY_ATTEMPTS . $patientHash);
        $this->cache->forget(self::CACHE_KEY_LAST_ATTEMPT . $patientHash);
    }

    private function clearRetryAfter(string $patientHash): void
    {
        $this->cache->forget(self::CACHE_KEY_RETRY_AFTER . $patientHash);
    }

    private function getNewRetryAfter(int $attempt): int
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
}
