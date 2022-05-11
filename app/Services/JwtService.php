<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Code;
use Firebase\JWT\JWT;

class JwtService
{
    protected string $privateKey;
    protected string $iss;
    protected string $aud;

    public function __construct(string $privateKeyPath, string $iss, string $aud)
    {
        $this->privateKey = (string)file_get_contents(base_path($privateKeyPath));
        $this->iss = $iss;
        $this->aud = $aud;
    }

    public function generate(Code $code): string
    {
        $payload = array(
            "iss" => $this->iss,
            "aud" => $this->aud,
            "iat" => time(),
            "nbf" => time(),
            "userHash" => $code->hash,
            "nonce" => hash('sha256', uniqid($code->hash, true)),
        );

        return JWT::encode($payload, $this->privateKey, 'RS256');
    }
}
