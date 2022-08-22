<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Carbon;

class PatientCodeGenerationThrottleService
{
    protected const CACHE_KEY_RETRY_AFTER = 'code_generation.retry_after.';
    protected const CACHE_KEY_ATTEMPTS = 'code_generation.attempts.';
    protected const CACHE_KEY_LAST_ATTEMPT = 'code_generation.last_attempt.';
    protected const CACHE_TTL = 60 * 60 * 24; // 1 day

    public function __construct(
        protected Repository $cache,
    ) {
    }

    public function attempt(string $patientHash): void
    {
        $attempts = $this->getAttempts($patientHash);

        $this->cache->put(
            self::CACHE_KEY_RETRY_AFTER . $patientHash,
            $this->getNewRetryAfter($attempts),
            self::CACHE_TTL
        );
        $this->cache->put(self::CACHE_KEY_ATTEMPTS . $patientHash, ++$attempts, self::CACHE_TTL);
        $this->cache->put(self::CACHE_KEY_LAST_ATTEMPT . $patientHash, time(), self::CACHE_TTL);
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

    protected function clearRetryAfter(string $patientHash): void
    {
        $this->cache->forget(self::CACHE_KEY_RETRY_AFTER . $patientHash);
    }

    protected function getNewRetryAfter(int $attempt): int
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
