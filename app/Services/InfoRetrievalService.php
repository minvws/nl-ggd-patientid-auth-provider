<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\InfoRetrievalGateway\InfoRetrievalGateway;

class InfoRetrievalService
{
    protected InfoRetrievalGateway $gateway;

    public function __construct(InfoRetrievalGateway $gateway)
    {
        $this->gateway = $gateway;
    }


    public function retrieve(string $hash): UserInfo
    {
        return $this->gateway->retrieve($hash);
    }
}
