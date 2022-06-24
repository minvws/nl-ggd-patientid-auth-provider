<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Firebase\JWT\JWT;

class JwtService
{
    protected string $privateKey;
    protected string $iss;
    protected string $aud;
    protected int $expiryTime;

    public function __construct(string $privateKeyPath, string $iss, string $aud, int $expiryTime)
    {
        $this->privateKey = (string)file_get_contents(base_path($privateKeyPath));
        $this->iss = $iss;
        $this->aud = $aud;
        $this->expiryTime = $expiryTime;
    }

    public function generate(string $userHash): string
    {
        $now = Carbon::now();

        $payload = array(
            "iss" => $this->iss,
            "aud" => $this->aud,
            "iat" => $now->getTimestamp(),
            "nbf" => $now->getTimestamp(),
            "exp" => $now->addSeconds($this->expiryTime)->getTimestamp(),
            "userHash" => $userHash,
            "nonce" => hash('sha256', uniqid($userHash, true)),
        );

        return JWT::encode($payload, $this->privateKey, 'RS256');
    }
}
