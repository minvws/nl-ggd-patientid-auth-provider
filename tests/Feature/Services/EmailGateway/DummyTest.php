<?php

declare(strict_types=1);

namespace Tests\Feature\Services\EmailGateway;

use App\Services\EmailGateway\Dummy;
use Tests\TestCase;

class DummyTest extends TestCase
{
    public function testDummy()
    {
        $gateway = new Dummy();

        $this->assertTrue($gateway->send("test@example.org", "", ["code" => "123456"]));
    }
}
