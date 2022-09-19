<?php

namespace Tests\Unit\Services;

use App\Services\EmailGateway\EmailGatewayInterface;
use App\Services\EmailService;
use App\Services\UserInfo;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserInfoTest extends TestCase
{
    use WithFaker;

    public function testUserInfoEmpty(): void
    {
        $userInfo = new UserInfo('random-hash');

        self::assertEquals('random-hash', $userInfo->hash);
        self::assertFalse($userInfo->hasEmail());
        self::assertFalse($userInfo->hasPhone());
        self::assertTrue($userInfo->isEmpty());
    }

    public function testUserInfoEmail(): void
    {
        $email = $this->faker->email();

        $userInfo = (new UserInfo('random-hash'))->withEmail($email);

        self::assertTrue($userInfo->hasEmail());
        self::assertEquals($email, $userInfo->email);
        self::assertFalse($userInfo->hasPhone());
        self::assertFalse($userInfo->isEmpty());
    }

    public function testUserInfoPhone(): void
    {
        $phoneNumber = $this->faker->phoneNumber();

        $userInfo = (new UserInfo('random-hash'))->withPhoneNumber($phoneNumber);

        self::assertTrue($userInfo->hasPhone());
        self::assertEquals($phoneNumber, $userInfo->phoneNumber);
        self::assertFalse($userInfo->hasEmail());
        self::assertFalse($userInfo->isEmpty());
    }

    public function testUserInfoEmailAndPhone(): void
    {
        $email = $this->faker->email();
        $phoneNumber = $this->faker->phoneNumber();

        $userInfo = (new UserInfo('random-hash'))
            ->withEmail($email)
            ->withPhoneNumber($phoneNumber);

        self::assertEquals($email, $userInfo->email);
        self::assertTrue($userInfo->hasEmail());
        self::assertEquals($phoneNumber, $userInfo->phoneNumber);
        self::assertTrue($userInfo->hasPhone());
        self::assertFalse($userInfo->isEmpty());
    }
}
