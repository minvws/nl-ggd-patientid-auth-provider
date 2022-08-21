<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Cache\Repository;

class VerificationCodeSentCacheService
{
    protected const CACHE_KEY_SENT_TO = 'verification_code.sent_to.';
    protected const CACHE_KEY_LAST_SENT_METHOD = 'verification_code.last_sent_method.';

    public function __construct(
        protected Repository $cache,
    ) {
    }

    public function getLastSentMethod(string $patientHash): ?string
    {
        return $this->cache->get(self::CACHE_KEY_LAST_SENT_METHOD . $patientHash);
    }

    public function getLastSentTo(string $patientHash): ?string
    {
        $method = $this->getLastSentMethod($patientHash);

        $sentTo = $this->cache->get(self::CACHE_KEY_SENT_TO . $patientHash, []);
        return $sentTo[$method] ?? null;
    }

    public function saveSentTo(string $patientHash, string $method, string $data): void
    {
        $sentTo = $this->cache->get(self::CACHE_KEY_SENT_TO . $patientHash, []);
        $sentTo[$method] = $data;

        $this->cache->put(self::CACHE_KEY_SENT_TO . $patientHash, $sentTo);
        $this->cache->put(self::CACHE_KEY_LAST_SENT_METHOD . $patientHash, $method);
    }

    public function clearCache(string $patientHash): void
    {
        $this->cache->delete(self::CACHE_KEY_SENT_TO . $patientHash);
        $this->cache->delete(self::CACHE_KEY_LAST_SENT_METHOD . $patientHash);
    }
}
