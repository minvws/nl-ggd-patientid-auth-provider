<?php

declare(strict_types=1);

namespace App\Services\InfoRetrievalGateway;

use App\Services\UserInfo;

interface InfoRetrievalGateway
{
    public function retrieve(string $userHash): UserInfo;
}
