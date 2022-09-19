<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\SmsGateway\SmsGatewayInterface;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SmsServiceTest extends TestCase
{
    use WithFaker;

    public function testIfSendIsCalled(): void
    {
        $phoneNumber = $this->faker->phoneNumber();
        $code = '123456';

        $gateway = $this->createMock(SmsGatewayInterface::class);
        $gateway->expects($this->once())->method('send')->with($phoneNumber, 'template', ['code' => $code]);

        $service = new SmsService($gateway);
        $service->send($phoneNumber, 'template', ['code' => $code]);
    }
}
