<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Cache\Repository;

class PatientCacheService
{
    protected const CACHE_KEY_PREFIX = 'patient.';
    protected const CACHE_KEY_SENT_TO = '.verification_code.sent_to';
    protected const CACHE_KEY_LAST_SENT_METHOD = '.verification_code.last_sent_method';
    protected const CACHE_KEY_HAS_PHONE = '.has_phone';
    protected const CACHE_KEY_HAS_EMAIL = '.has_email';
    protected const CACHE_TTL = 60 * 60 * 24; // 1 day

    public function __construct(
        protected Repository $cache,
    ) {
    }

    public function getLastSentMethod(string $patientHash): ?string
    {
        return $this->cache->get($this->getCacheKey(self::CACHE_KEY_LAST_SENT_METHOD, $patientHash));
    }

    public function getLastSentTo(string $patientHash): ?string
    {
        $method = $this->getLastSentMethod($patientHash);

        $sentTo = $this->getSentTo($patientHash);
        return $sentTo[$method] ?? null;
    }

    public function getSentTo(string $patientHash): array
    {
        return $this->cache->get($this->getCacheKey(self::CACHE_KEY_SENT_TO, $patientHash), []);
    }

    public function saveSentTo(string $patientHash, string $method, string $data): void
    {
        $sentTo = $this->getSentTo($patientHash);
        $sentTo[$method] = $data;

        $this->cache->put($this->getCacheKey(self::CACHE_KEY_SENT_TO, $patientHash), $sentTo, self::CACHE_TTL);
        $this->setLastSentTo($patientHash, $method);
    }

    public function setLastSentTo(string $patientHash, string $method): void
    {
        $this->cache->put($this->getCacheKey(self::CACHE_KEY_LAST_SENT_METHOD, $patientHash), $method, self::CACHE_TTL);
    }

    public function getHasPhone(string $patientHash): bool
    {
        return $this->cache->get($this->getCacheKey(self::CACHE_KEY_HAS_PHONE, $patientHash), false);
    }

    public function getHasEmail(string $patientHash): bool
    {
        return $this->cache->get($this->getCacheKey(self::CACHE_KEY_HAS_EMAIL, $patientHash), false);
    }

    public function setHasPhone(string $patientHash, bool $has): void
    {
        $this->cache->put($this->getCacheKey(self::CACHE_KEY_HAS_PHONE, $patientHash), $has, self::CACHE_TTL);
    }

    public function setHasEmail(string $patientHash, bool $has): void
    {
        $this->cache->put($this->getCacheKey(self::CACHE_KEY_HAS_EMAIL, $patientHash), $has, self::CACHE_TTL);
    }

    public function clearCache(string $patientHash): void
    {
        $this->cache->delete($this->getCacheKey(self::CACHE_KEY_SENT_TO, $patientHash));
        $this->cache->delete($this->getCacheKey(self::CACHE_KEY_LAST_SENT_METHOD, $patientHash));
        $this->cache->delete($this->getCacheKey(self::CACHE_KEY_HAS_PHONE, $patientHash));
        $this->cache->delete($this->getCacheKey(self::CACHE_KEY_HAS_EMAIL, $patientHash));
    }

    protected function getCacheKey(string $cacheKey, string $patientHash): string
    {
        return self::CACHE_KEY_PREFIX . $patientHash . $cacheKey;
    }
}
