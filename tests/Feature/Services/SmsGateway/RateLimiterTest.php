<?php

declare(strict_types=1);

namespace Tests\Feature\Services\SmsGateway;

use App\Services\SmsGateway\RateLimiter;
use App\Services\SmsGateway\SmsGatewayInterface;
use Tests\TestCase;

class RateLimiterTest extends TestCase
{
    /**
     * This will test if the send method of the provider is called.
     */
    public function testSend(): void
    {
        $provider = $this->createMock(SmsGatewayInterface::class);
        $provider->expects($this->once())->method('send');

        $service = new RateLimiter($provider, 1);
        $service->send('1234567890', 'template', ['code' => '123456']);
    }

    /**
     * This will test if the rate limiter will kick in.
     */
    public function testRateLimiting(): void
    {
        $provider = $this->createMock(SmsGatewayInterface::class);
        $provider->expects($this->once())->method('send')->willReturn(true);

        $service = new RateLimiter($provider, 1);
        self::assertTrue($service->send('1234567890', 'template', ['code' => '123456']));
        self::assertFalse($service->send('1234567890', 'template', ['code' => '123456']));
        self::assertFalse($service->send('1234567890', 'template', ['code' => '123456']));
    }

    /**
     * This will test if the rate limiter will kick in.
     */
    public function testRateLimitingTwice(): void
    {
        $provider = $this->createMock(SmsGatewayInterface::class);
        $provider->expects($this->exactly(2))->method('send')->willReturn(true);

        $service = new RateLimiter($provider, 2);
        self::assertTrue($service->send('1234567890', 'template', ['code' => '123456']));
        self::assertTrue($service->send('1234567890', 'template', ['code' => '123456']));
        self::assertFalse($service->send('1234567890', 'template', ['code' => '123456']));
    }

    /**
     * This will test if the rate limiter will kick in with other phone number.
     */
    public function testRateLimitingWithOtherPhoneNumber(): void
    {
        $provider = $this->createMock(SmsGatewayInterface::class);
        $provider->expects($this->exactly(2))->method('send')->willReturn(true);

        $service = new RateLimiter($provider, 1);
        self::assertTrue($service->send('1234567890', 'template', ['code' => '123456']));
        self::assertFalse($service->send('1234567890', 'template', ['code' => '123456']));

        self::assertTrue($service->send('2345678912', 'template', ['code' => '123456']));
        self::assertFalse($service->send('2345678912', 'template', ['code' => '123456']));
    }
}
