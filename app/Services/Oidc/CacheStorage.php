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

    public function fetchAuthData(string $code): array | null
    {
        return Cache::get('ac_' . $code);
    }
}
