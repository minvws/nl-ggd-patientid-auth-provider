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

        $this->dummyData = [
            $this->createHash('12345678', '1976-10-16') => (new UserInfo())->withPhoneNr('06-123456789'),
            $this->createHash('12345678', '1980-01-01') => (new UserInfo())->withEmail('user@example.org'),
        ];
    }


    public function retrieve(string $userHash): UserInfo
    {
        if (! isset($this->dummyData[$userHash])) {
            return new UserInfo();
        }

        return $this->dummyData[$userHash];
    }

    protected function createHash(string $patientId, string $birthDate): string
    {
        return hash_hmac('sha256', $patientId . '-' . $birthDate, $this->hmacKey);
    }
}
