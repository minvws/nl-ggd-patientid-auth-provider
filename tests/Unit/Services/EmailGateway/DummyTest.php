<?php

declare(strict_types=1);

namespace Tests\Unit\Services\EmailGateway;

use App\Services\EmailGateway\Dummy;
use PHPUnit\Framework\TestCase;

class DummyTest extends TestCase
{
    public function testDummy()
    {
        $gateway = new Dummy();

        $this->assertTrue($gateway->send("test@example.org", "", ["code" => "123456"]));
    }
}
