<?php

declare(strict_types=1);

namespace App\Services\Oidc;

interface StorageInterface
{
    public function saveAuthData(string $code, array $authData): void;
    public function fetchAuthData(string $code): mixed;

    public function saveAccessToken(string $accessToken): void;
    public function accessTokenExists(string $accessToken): bool;
}
