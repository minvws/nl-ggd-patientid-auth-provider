<?php

declare(strict_types=1);

namespace App\Services\InfoRetrievalGateway;

interface InfoRetrievalGateway
{
    public function retrieve(string $userHash): array;
}
