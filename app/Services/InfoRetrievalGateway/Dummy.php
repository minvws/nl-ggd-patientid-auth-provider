<?php

declare(strict_types=1);

namespace App\Services\InfoRetrievalGateway;

class Dummy implements InfoRetrievalGateway
{
    protected array $dummyData = [
        // 1234567 - 1976-10-16
        'cc0187181eedbfd169fb5e2ce60392da6916282fc60d01b403a1649525054d61' => [
            'phoneNumber' => '06-123456789',
        ],
        // 1234567 - 1980-01-01
        'a8864cfd4a3c47a38bce983065af651b8a9c8e656b4902860e24d9ea18aee57a' => [
            'email' => 'user@example.org',
        ],
    ];

    public function retrieve(string $hash): array
    {
        if (! isset($this->dummyData[$hash])) {
            return [];
        }

        return array_merge([
            'protocolVersion' => '3.0',
            'providerIdentifier' => 'xxx',
        ], $this->dummyData[$hash]);
    }
}
