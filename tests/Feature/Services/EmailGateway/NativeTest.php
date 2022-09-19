<?php

declare(strict_types=1);

namespace Tests\Feature\Services\EmailGateway;

use App\Mail\SendCode;
use App\Services\EmailGateway\Native;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NativeTest extends TestCase
{
    use WithFaker;

    /**
     * This will test if the email is sent.
     */
    public function testSend(): void
    {
        Mail::fake();

        $email = $this->faker->email();
        $smsCode = '123456';

        $service = new Native();
        $service->send($email, 'template', ['code' => $smsCode]);

        Mail::assertSent(SendCode::class, static function (SendCode $mail) use ($email) {
            return $mail->hasTo($email);
        });
    }
}
