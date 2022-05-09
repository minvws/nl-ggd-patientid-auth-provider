<?php

declare(strict_types=1);

namespace App\Services\Oidc;

interface ClientResolverInterface {
    /**
     * Resolves a client id to a client object or null when client id is not found
     */
    public function resolve(string $clientId): ?Client;

    /**
     * Returns true when a client-id could be resolved, false otherwise
     */
    public function exists(string $clientId): bool;
}
