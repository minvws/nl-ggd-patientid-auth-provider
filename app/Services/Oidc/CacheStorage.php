<?php

declare(strict_types=1);

namespace App\Services\Oidc;

use Illuminate\Support\Facades\Cache;

/**
 * Storage adapter to store data into the laravel cache
 */
class CacheStorage implements StorageInterface
{
    public function saveAuthData(string $code, array $authData): void
    {
        Cache::put('ac_' . $code, $authData, 60 * 10);
    }

    /**
     * @param string $code
     * @return array|mixed
     */
    public function fetchAuthData(string $code): mixed
    {
        return Cache::get('ac_' . $code);
    }

    public function saveAccessToken(string $accessToken): void
    {
        Cache::put('at_' . $accessToken, $accessToken, 3600);
    }

    public function accessTokenExists(string $accessToken): bool
    {
        return Cache::has('at_' . $accessToken);
    }
}
