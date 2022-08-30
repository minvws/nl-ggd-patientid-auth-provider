<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Firebase\JWT\JWT;

class JwtService
{
    protected string $privateKeyPath;
    protected string $certificatePath;
    protected string $iss;
    protected string $aud;
    protected int $expiryTime;

    public function __construct(
        string $privateKeyPath,
        string $certificatePath,
        string $iss,
        string $aud,
        int $expiryTime
    ) {
        $this->privateKeyPath = $privateKeyPath;
        $this->certificatePath = $certificatePath;
        $this->iss = $iss;
        $this->aud = $aud;
        $this->expiryTime = $expiryTime;
    }

    public function generate(string $userHash): string
    {
        $privateKey = (string)file_get_contents(base_path($this->privateKeyPath));
        $certificate = (string)file_get_contents(base_path($this->certificatePath));

        $now = Carbon::now();

        $payload = array(
            "kid" => hash('sha256', $certificate),
            "iss" => $this->iss,
            "aud" => $this->aud,
            "iat" => $now->getTimestamp(),
            "nbf" => $now->getTimestamp(),
            "exp" => $now->addSeconds($this->expiryTime)->getTimestamp(),
            "userHash" => $userHash,
            "nonce" => hash('sha256', uniqid($userHash, true)),
        );

        return JWT::encode($payload, $privateKey, 'RS256');
    }
}
