<?php

declare(strict_types=1);

namespace App\Services\Oidc;

/**
 * Resolves clients through an array. Mostly used for unit-tests
 */
class ArrayClientResolver implements ClientResolverInterface
{
    protected array $clients;

    public function __construct(array $clientConfig)
    {
        $this->clients = [];
        foreach ($clientConfig as $clientId => $clientData) {
            $this->clients[$clientId] = new Client($clientId, $clientData['name'], $clientData['redirect_uris'] ?? []);
        }
    }

    public function resolve(string $clientId): ?Client
    {
        return $this->clients[$clientId] ?? null;
    }

    public function exists(string $clientId): bool
    {
        return isset($this->clients[$clientId]);
    }
}
