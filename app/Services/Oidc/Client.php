<?php

declare(strict_types=1);

namespace App\Services\Oidc;

/**
 * An oauth/oidc client with additional information
 */
class Client
{
    protected string $client_id;
    protected array $redirect_uris;

    public function __construct(string $client_id, array $redirect_uris)
    {
        $this->client_id = $client_id;
        $this->redirect_uris = $redirect_uris;
    }

    public function getClientId(): string
    {
        return $this->client_id;
    }

    public function getRedirectUris(): array
    {
        return $this->redirect_uris;
    }
}
