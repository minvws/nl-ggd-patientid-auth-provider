<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\EmailGateway\EmailGatewayInterface;
use App\Services\EmailService;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EmailServiceTest extends TestCase
{
    use WithFaker;

    public function testIfSendIsCalled(): void
    {
        $email = $this->faker->email();
        $code = '123456';

        $gateway = $this->createMock(EmailGatewayInterface::class);
        $gateway->expects($this->once())->method('send')->with($email, 'template', ['code' => $code]);

        $service = new EmailService($gateway);
        $service->send($email, 'template', ['code' => $code]);
    }
}
