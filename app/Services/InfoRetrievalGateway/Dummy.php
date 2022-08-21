<?php

declare(strict_types=1);

namespace App\Services\InfoRetrievalGateway;

use App\Services\UserInfo;

class Dummy implements InfoRetrievalGateway
{
    protected string $hmacKey;
    protected array $dummyData;

    public function __construct(string $hmacKey)
    {
        $this->hmacKey = $hmacKey;

        $this->dummyData = [];

        $hash = $this->createHash('12345678', '1976-10-16');
        $this->dummyData[$hash] = (new UserInfo($hash))->withPhoneNumber('06-123456789');

        $hash = $this->createHash('12345678', '1980-01-01');
        $this->dummyData[$hash] = (new UserInfo($hash))->withEmail('user@example.org');

        $hash = $this->createHash('12345678', '1981-01-01');
        $this->dummyData[$hash] = (new UserInfo($hash))
            ->withEmail('user@example.org')
            ->withPhoneNumber('06-123456789');
    }


    public function retrieve(string $userHash): UserInfo
    {
        return $this->dummyData[$userHash] ?? new UserInfo($userHash);
    }

    protected function createHash(string $patientId, string $birthDate): string
    {
        return hash_hmac('sha256', $patientId . '-' . $birthDate, $this->hmacKey);
    }
}
