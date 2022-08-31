<?php

declare(strict_types=1);

namespace App\Services\Oidc;

/**
 * Resolves clients through a JSON configuration file
 */
class JsonClientResolver implements ClientResolverInterface
{
    protected array $clients;

    public function __construct(string $clientConfigPath)
    {
        $content = file_get_contents($clientConfigPath);
        if ($content === false) {
            $content = '[]';
        }


        $clients = json_decode((string)$content, true);
        if ($clients === false) {
            $clients = [];
        }

        $this->clients = [];
        foreach ($clients as $clientId => $clientData) {
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
