<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Models\Code;
use App\Services\CodeGeneratorService;
use App\Services\EmailGateway\Dummy as EmailGatewayDummy;
use App\Services\EmailService;
use App\Services\InfoRetrievalService;
use App\Services\InfoRetrievalGateway\Dummy as InfoRetrievalGatewayDummy;
use App\Services\Oidc\ClientResolverInterface;
use App\Services\Oidc\JsonClientResolver;
use App\Services\OidcParams;
use App\Services\PatientCacheService;
use App\Services\SmsGateway\Dummy as SmsGatewayDummy;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class OidcControllerTest extends TestCase
{
    use DatabaseMigrations;

    public function setup(): void
    {
        parent::setUp();

        $this->bindDummyServices();
    }

    /**
     * Test visiting /oidc/authorize route
     */
    public function testAuthorize(): void
    {
        // phpcs:ignore
        $response = $this->get('/oidc/authorize?' . http_build_query($this->getRawOidcParams()));

        $response->assertStatus(302);
        $response->assertRedirect(route('start_auth'));
    }

    public function testStartAuth(): void
    {
        $response = $this
            ->withSession([
                'oidcparams' => $this->getOidcParamsWithClient(),
            ])
            ->get(route('start_auth'));

        $response->assertOk();
        $response->assertSee('Client 123');
    }

    public function testLoginSubmit(): void
    {
        $response = $this
            ->withSession([
                'oidcparams' => $this->getOidcParamsWithClient(),
            ])
            ->post(route('start_auth'), [
                'patient_id' => '12345678',
                'birth_year' => '1976',
                'birth_month' => '10',
                'birth_day' => '16',
            ]);

        $response->assertRedirect(route('verify'));
    }

    public function testGetVerify(): void
    {
        $patientHash = app(CodeGeneratorService::class)->createHash('12345678', '1976-10-16');

        // Pretend that we sent the sms
        app(PatientCacheService::class)->saveSentTo($patientHash, 'sms', '***');

        $response = $this
            ->withSession([
                'oidcparams' => $this->getOidcParamsWithClient(),
                'hash' => $patientHash,
            ])
            ->get(route('verify'));

        $response->assertOk();
        $response->assertViewIs('verify');
        $response->assertSee(__('verify.header'));
    }

    public function testSubmitVerify(): void
    {
        $patientHash = app(CodeGeneratorService::class)->createHash('12345678', '1976-10-16');

        // Generate a code
        $code = app(CodeGeneratorService::class)->generate($patientHash);

        // Pretend that we sent the sms
        app(PatientCacheService::class)->saveSentTo($patientHash, 'sms', '***');

        $response = $this
            ->withSession([
                'oidcparams' => $this->getOidcParamsWithClient(),
                'hash' => $patientHash,
            ])
            ->post(route('verify'), [
                'code' => $code->code,
            ]);

        $response->assertRedirectContains($this->getOidcParams()->redirectUri);
    }

    public function testGetResend(): void
    {
        $patientHash = app(CodeGeneratorService::class)->createHash('12345678', '1976-10-16');

        // Pretend that we sent the sms
        app(PatientCacheService::class)->saveSentTo($patientHash, 'sms', '***');

        $response = $this
            ->withSession([
                'oidcparams' => $this->getOidcParamsWithClient(),
                'hash' => $patientHash,
            ])
            ->get(route('resend'));

        $response->assertOk();
        $response->assertViewIs('resend');
        $response->assertSee(__('resend.header.sms'));
    }

    public function testGetResendWithoutSent(): void
    {
        $patientHash = app(CodeGeneratorService::class)->createHash('12345678', '1976-10-16');

        $response = $this
            ->withSession([
                'oidcparams' => $this->getOidcParamsWithClient(),
                'hash' => $patientHash,
            ])
            ->get(route('resend'));

        $response->assertRedirect(route('start_auth'));
    }

    public function testSumbitResend(): void
    {
        $patientHash = app(CodeGeneratorService::class)->createHash('12345678', '1976-10-16');

        $response = $this
            ->withSession([
                'oidcparams' => $this->getOidcParamsWithClient(),
                'hash' => $patientHash,
            ])
            ->post(route('resend.submit'));

        $response->assertRedirect(route('verify'));
    }

    public function testSumbitResendWithNonExpiredCode(): void
    {
        $patientHash = app(CodeGeneratorService::class)->createHash('12345678', '1976-10-16');

        // Generate a code
        $code = app(CodeGeneratorService::class)->generate($patientHash);

        // Pretend that we sent the sms
        app(PatientCacheService::class)->saveSentTo($patientHash, 'sms', '***');

        $response = $this
            ->withSession([
                'oidcparams' => $this->getOidcParamsWithClient(),
                'hash' => $patientHash,
            ])
            ->post(route('resend.submit'));

        // Pretend the code is send...
        $response->assertRedirect(route('verify'));
    }

    public function testSumbitResendWithExpiredCode(): void
    {
        $patientHash = app(CodeGeneratorService::class)->createHash('12345678', '1976-10-16');

        Carbon::setTestNow(Carbon::now()->subMinutes(30));

        // Generate a code
        $code = app(CodeGeneratorService::class)->generate($patientHash);

        // Pretend that we sent the sms
        app(PatientCacheService::class)->saveSentTo($patientHash, 'sms', '***');

        Carbon::setTestNow();

        $response = $this
            ->withSession([
                'oidcparams' => $this->getOidcParamsWithClient(),
                'hash' => $patientHash,
            ])
            ->post(route('resend.submit'));

        // Check if previously generated code is not the current code
        self::assertNotEquals($code->code, Code::whereHash($patientHash)->first()->code);

        // New code is generated and send...
        $response->assertRedirect(route('verify'));
    }

    protected function getOidcParamsWithClient(): OidcParams
    {
        $oidcParams = $this->getOidcParams();

        $client = $this->getClientResolver()->resolve($oidcParams->clientId);
        $oidcParams->set('client', $client);

        return $oidcParams;
    }

    protected function getOidcParams(): OidcParams
    {
        return OidcParams::fromArray($this->getRawOidcParams());
    }

    protected function getRawOidcParams(): array
    {
        return [
            'response_type' => 'code',
            'client_id' => 'client-123',
            'state' => 'a',
            'scope' => 'openid',
            'redirect_uri' => 'https://localhost:445/callback.html',
            'code_challenge' => 'oAh_MAHECKHJUmXa9iWFWSybV45sm-pUQPSG5-BB_xc',
            'code_challenge_method' => 'S256',
        ];
    }

    protected function getClientResolver(): ClientResolverInterface
    {
        return app(JsonClientResolver::class);
    }

    protected function bindDummyServices(): void
    {
        App::bind(InfoRetrievalService::class, function () {
            return new InfoRetrievalService(
                new InfoRetrievalGatewayDummy(
                    config('codegenerator.hmac_key', '')
                )
            );
        });

        App::bind(EmailService::class, function () {
            return new EmailService(new EmailGatewayDummy());
        });

        App::bind(SmsService::class, function () {
            return new SmsService(new SmsGatewayDummy());
        });
    }
}
