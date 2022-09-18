<?php

declare(strict_types=1);

namespace Tests\Feature\Services\SmsGateway;

use App\Services\SmsGateway\Dummy;
use Tests\TestCase;

class DummyTest extends TestCase
{
    public function testDummy()
    {
        $gateway = new Dummy();

        $this->assertTrue($gateway->send("0600000000", "", ["code" => "123456"]));
    }
}
