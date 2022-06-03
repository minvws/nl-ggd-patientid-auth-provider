<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Oidc\ArrayClientResolver;
use App\Services\Oidc\StorageInterface;
use App\Services\OidcService;
use App\Services\JwtService;
use Illuminate\Http\Request;
use Mockery;
use Spatie\Url\Url;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Tests\TestCase;

class OidcServiceTest extends TestCase
{
    protected Mockery\LegacyMockInterface|StorageInterface|Mockery\MockInterface $storageMock;
    protected ArrayClientResolver $clientResolver;
    protected JwtService $jwtService;

    public function testMissingValues()
    {
        $oidcService = $this->setupService();

        $request = new Request();

        $this->expectExceptionObject(new BadRequestHttpException());
        $this->expectExceptionMessage('incomplete set of request data found');
        $oidcService->authorize($request);
    }

    public function testMissingCodeChallenge()
    {
        $oidcService = $this->setupService();

        $request = new Request();
        $request = $request->replace([
            'response_type' => 'not-code',
            'client_id' => 'foo',
            'state' => 'state',
            'scope' => 'scope',
            'redirect_uri' => 'https://',
            'code_challenge' => 'code',
            'code_challenge_method' => 'S256',
        ]);

        $this->expectExceptionObject(new BadRequestHttpException());
        $this->expectExceptionMessage('code expected as response type');
        $oidcService->authorize($request);
    }

    public function testIncorrectHashingMethod()
    {
        $oidcService = $this->setupService();

        $request = new Request();
        $request = $request->replace([
            'response_type' => 'code',
            'client_id' => 'foo',
            'state' => 'state',
            'scope' => 'scope',
            'redirect_uri' => 'https://',
            'code_challenge' => '8juoLS5oOXU8-tJzjQdAHrqrl7QF6LlnPoC6uRtPNuE',
            'code_challenge_method' => 'plain',
        ]);

        $this->expectExceptionObject(new BadRequestHttpException());
        $this->expectExceptionMessage('incorrect hashing method');
        $oidcService->authorize($request);
    }

    public function testIncorrectClientId()
    {
        $oidcService = $this->setupService();

        $request = new Request();
        $request = $request->replace([
            'response_type' => 'code',
            'client_id' => 'unknown',
            'state' => 'state',
            'scope' => 'scope',
            'redirect_uri' => 'https://',
            'code_challenge' => '8juoLS5oOXU8-tJzjQdAHrqrl7QF6LlnPoC6uRtPNuE',
            'code_challenge_method' => 'S256',
        ]);

        $this->expectExceptionObject(new BadRequestHttpException());
        $this->expectExceptionMessage('incorrect client id');
        $oidcService->authorize($request);
    }

    public function testIncorrectRedirectUrl()
    {
        $oidcService = $this->setupService();

        $request = new Request();
        $request = $request->replace([
            'response_type' => 'code',
            'client_id' => 'client_123',
            'state' => 'state',
            'scope' => 'scope',
            'redirect_uri' => 'https://not-correct',
            'code_challenge' => '8juoLS5oOXU8-tJzjQdAHrqrl7QF6LlnPoC6uRtPNuE',
            'code_challenge_method' => 'S256',
        ]);

        $this->expectExceptionObject(new BadRequestHttpException());
        $this->expectExceptionMessage('invalid redirect uri specified');
        $oidcService->authorize($request);
    }

    public function testCorrectData()
    {
        $oidcService = $this->setupService();

        $request = new Request();
        $request = $request->replace([
            'response_type' => 'code',
            'client_id' => 'client_123',
            'state' => 'the-state',
            'scope' => 'scope',
            'redirect_uri' => 'https://foo',
            'code_challenge' => '8juoLS5oOXU8-tJzjQdAHrqrl7QF6LlnPoC6uRtPNuE',
            'code_challenge_method' => 'S256',
        ]);

        $this->storageMock->shouldReceive('saveAuthData')->once();

        $response = $oidcService->authorize($request);

        $url = Url::fromString($response->getTargetUrl());
        $queryParams = $url->getAllQueryParameters();
        $this->assertEquals($queryParams['state'], 'the-state');
        $this->assertNotEmpty($queryParams['code']);
    }

    public function testAccessTokenAuthorizationCode()
    {
        $oidcService = $this->setupService();

        $request = new Request();
        $request = $request->replace([
            'grant_type' => 'wrong-grant-type',
            'code' => '0000000000000000000',
            'redirect_uri' => 'https://foo',
            'code_verifier' => 'ps0xAme1TcZTOTZD1Nx85DWZZWIzhMAIcll84BbGK2o',
            'code_challenge_method' => 'S256',
            'client_id' => 'another-client-id',
        ]);

        $this->expectExceptionObject(new BadRequestHttpException());
        $this->expectExceptionMessage('authorization_code expected as response type');
        $oidcService->accessToken($request);
    }

    public function testAccessTokenWrongClientId()
    {
        $oidcService = $this->setupService();

        $request = new Request();
        $request = $request->replace([
            'grant_type' => 'authorization_code',
            'code' => '0000000000000000000',
            'redirect_uri' => 'https://foo',
            'code_verifier' => 'ps0xAme1TcZTOTZD1Nx85DWZZWIzhMAIcll84BbGK2o',
            'code_challenge_method' => 'S256',
            'client_id' => 'another-client-id',
        ]);

        $this->storageMock->shouldReceive('fetchAuthData')->with('0000000000000000000')->once()->andReturnFalse();

        $this->expectExceptionObject(new BadRequestHttpException());
        $this->expectExceptionMessage('code not found or expired');
        $oidcService->accessToken($request);
    }

    public function testIncorrectRedirectUri()
    {
        $oidcService = $this->setupService();

        $request = new Request();
        $request = $request->replace([
            'grant_type' => 'authorization_code',
            'code' => '0000000000000000000',
            'redirect_uri' => 'https://bar',
            'code_verifier' => 'ps0xAme1TcZTOTZD1Nx85DWZZWIzhMAIcll84BbGK2o',
            'code_challenge_method' => 'S256',
            'client_id' => 'client_123',
        ]);

        $this->storageMock->shouldReceive('fetchAuthData')->with('0000000000000000000')->once()->andReturns([
            'response_type' => 'code',
            'client_id' => 'client_123',
            'state' => 'the-state',
            'scope' => 'scope',
            'redirect_uri' => 'https://foo',
            'code_challenge' => '8juoLS5oOXU8-tJzjQdAHrqrl7QF6LlnPoC6uRtPNuE',
            'code_challenge_method' => 'S256',
        ]);

        $this->expectExceptionObject(new BadRequestHttpException());
        $this->expectExceptionMessage('incorrect redirect uri');
        $oidcService->accessToken($request);
    }

    public function testBadChallenge()
    {
        $oidcService = $this->setupService();

        $request = new Request();
        $request = $request->replace([
            'grant_type' => 'authorization_code',
            'code' => '0000000000000000000',
            'redirect_uri' => 'https://foo',
            'code_verifier' => 'ps0xAme1TcZTOTZD1Nx85DWZZWIzhMAIcll84BbGK2o',
            'code_challenge_method' => 'S256',
            'client_id' => 'client_123',
        ]);

        $this->storageMock->shouldReceive('fetchAuthData')->with('0000000000000000000')->once()->andReturns([
            'response_type' => 'code',
            'client_id' => 'client_123',
            'state' => 'the-state',
            'scope' => 'scope',
            'redirect_uri' => 'https://foo',
            'code_challenge' => 'vjoIV5YM7fQ7SaITcH0IZQ5RgC9u5q8CgVCaH_u02Oc',
            'code_challenge_method' => 'S256',
        ]);

        $this->expectExceptionObject(new BadRequestHttpException());
        $this->expectExceptionMessage('bad challenge');
        $oidcService->accessToken($request);
    }

    public function testCorrectChallenge()
    {
        $oidcService = $this->setupService();

        $request = new Request();
        $request = $request->replace([
            'grant_type' => 'authorization_code',
            'code' => '0000000000000000000',
            'redirect_uri' => 'https://foo',
            'code_verifier' => 'ps0xAme1TcZTOTZD1Nx85DWZZWIzhMAIcll84BbGK2o',
            'code_challenge_method' => 'S256',
            'client_id' => 'client_123',
        ]);

        $this->storageMock->shouldReceive('fetchAuthData')->with('0000000000000000000')->once()->andReturns([
            'response_type' => 'code',
            'client_id' => 'client_123',
            'state' => 'the-state',
            'scope' => 'scope',
            'redirect_uri' => 'https://foo',
            'code_challenge' => '8juoLS5oOXU8-tJzjQdAHrqrl7QF6LlnPoC6uRtPNuE',
            'code_challenge_method' => 'S256',
        ]);

        $this->storageMock->shouldReceive('saveAccessToken')->once();

        $response = $oidcService->accessToken($request);

        $token = json_decode($response->getContent(), true);
        $this->assertNotEmpty($token['access_token']);
        $this->assertEquals(3600, $token['expires_in']);
        $this->assertEquals('bearer', $token['token_type']);
    }


    protected function setupService()
    {
        $this->storageMock = Mockery::mock(StorageInterface::class);
        $this->clientResolver = new ArrayClientResolver([
            'client_123' => [
                'redirect_uris' => [
                    'https://foo',
                    'https://bar',
                    'https://baz'
                ]
            ],
            'client_test' => [
                'redirect_uris' => [
                    'https://test.com',
                ]
            ]
        ]);
        $this->jwtService = new JwtService(
            config('jwt.private_key_path'),
            config('jwt.iss'),
            config('jwt.aud'),
        );

        return new OidcService(
            $this->clientResolver,
            $this->storageMock,
            $this->jwtService
        );
    }
}
