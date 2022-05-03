<?php

declare(strict_types=1);

namespace Tests\Unit\Services\SmsGateway;

use App\Services\SmsGateway\Dummy;
use PHPUnit\Framework\TestCase;

class DummyTest extends TestCase
{
    public function testDummy()
    {
        $gateway = new Dummy();

        $this->assertTrue($gateway->send("0600000000", "", []));
    }
}
