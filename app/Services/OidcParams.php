<?php

declare(strict_types=1);

namespace App\Services;

class OidcParams
{
    public string $responseType;
    public string $clientId;
    public string $state;
    public string $scope;
    public string $redirectUri;
    public string $codeChallenge;
    public string $codeChallengeMethod;

    public array $params = [];

    public static function fromArray(array $params): self
    {
        $oidcParams = new self();

        $oidcParams->responseType = $params['response_type'] ?? '';
        $oidcParams->clientId = $params['client_id'] ?? '';
        $oidcParams->state = $params['state'] ?? '';
        $oidcParams->scope = $params['scope'] ?? '';
        $oidcParams->redirectUri = $params['redirect_uri'] ?? '';
        $oidcParams->codeChallenge = $params['code_challenge'] ?? '';
        $oidcParams->codeChallengeMethod = $params['code_challenge_method'] ?? '';

        unset($params['response_type']);
        unset($params['client_id']);
        unset($params['state']);
        unset($params['scope']);
        unset($params['redirect_uri']);
        unset($params['code_challenge']);
        unset($params['code_challenge_method']);

        $oidcParams->params = $params;

        return $oidcParams;
    }

    public function set(string $key, mixed $value): void
    {
        $this->params[$key] = $value;
    }

    public function get(string $key): mixed
    {
        return $this->params[$key];
    }

    public function clear(string $key): void
    {
        unset($this->params[$key]);
    }

    public function has(string $key): bool
    {
        return isset($this->params[$key]);
    }

    public function toArray(): array
    {
        return array_merge([
            'response_type' => $this->responseType,
            'client_id' => $this->clientId,
            'state' => $this->state,
            'scope' => $this->scope,
            'redirect_uri' => $this->redirectUri,
            'code_challenge' => $this->codeChallenge,
            'code_challenge_method' => $this->codeChallengeMethod,
        ], $this->params);
    }
}
