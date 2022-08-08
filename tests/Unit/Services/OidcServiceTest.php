<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Oidc\ArrayClientResolver;
use App\Services\Oidc\StorageInterface;
use App\Services\OidcService;
use App\Services\JwtService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mockery;
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

        try {
            $oidcService->authorize($request);
            $this->fail();
        } catch (BadRequestHttpException $e) {
            // unknown client, so we cannot redirect
            $this->assertEquals('invalid_request', $e->getPrevious()->getMessage());
            $this->assertFalse($e->getPrevious()->canRedirect());
        }
    }

    public function testMissingCodeChallenge()
    {
        $oidcService = $this->setupService();

        $request = new Request();
        $request = $request->replace([
            'response_type' => 'not-code',
            'client_id' => 'unknown-client',
            'state' => 'state',
            'scope' => 'scope',
            'redirect_uri' => 'https://',
            'code_challenge' => 'code',
            'code_challenge_method' => 'S256',
        ]);

        try {
            $oidcService->authorize($request);
            $this->fail();
        } catch (BadRequestHttpException $e) {
            // unknown client, so we cannot redirect
            $this->assertEquals('unauthorized_client', $e->getPrevious()->getMessage());
            $this->assertFalse($e->getPrevious()->canRedirect());
        }
    }

    public function testIncorrectResponseTypeOnClient()
    {
        $oidcService = $this->setupService();

        $request = new Request();
        $request = $request->replace([
            'response_type' => 'something_else',
            'client_id' => 'client_123',
            'state' => 'newstate',
            'scope' => 'scope',
            'redirect_uri' => 'https://foo',
            'code_challenge' => 'code',
            'code_challenge_method' => 'S256',
        ]);

        $response = $oidcService->authorize($request);
        $this->assertNotNull($response);
        $this->assertEquals('https://foo?state=newstate&error=unsupported_response_type', $response->getTargetUrl());
    }

    public function testIncorrectCodeChallengeMethod()
    {
        $oidcService = $this->setupService();

        $request = new Request();
        $request = $request->replace([
            // data from original /authorize
            'response_type' => 'code',
            'client_id' => 'client_123',
            'state' => 'newstate',
            'scope' => 'scope',
            'redirect_uri' => 'https://foo',
            'code_challenge' => '8juoLS5oOXU8-tJzjQdAHrqrl7QF6LlnPoC6uRtPNuE',
            'code_challenge_method' => 'plain',

            // user hash, added in finishAuthorize
            'hash' => 'asdfasdfasdfasdf',
        ]);

        $response = $oidcService->authorize($request);
        $this->assertNotNull($response);
        $this->assertEquals('https://foo?state=newstate&error=invalid_request', $response->getTargetUrl());
    }

    public function testIncorrectClientId()
    {
        $oidcService = $this->setupService();

        $request = new Request();
        $request = $request->replace([
            // data from original /authorize
            'response_type' => 'code',
            'client_id' => 'unknown',
            'state' => 'state',
            'scope' => 'scope',
            'redirect_uri' => 'https://foo',
            'code_challenge' => '8juoLS5oOXU8-tJzjQdAHrqrl7QF6LlnPoC6uRtPNuE',
            'code_challenge_method' => 'S256',

            // user hash, added in finishAuthorize
            'hash' => 'asdfasdfasdfasdf',
        ]);

        try {
            $oidcService->authorize($request);
            $this->fail();
        } catch (BadRequestHttpException $e) {
            // unknown client, so we cannot redirect
            $this->assertEquals('unauthorized_client', $e->getPrevious()->getMessage());
            $this->assertFalse($e->getPrevious()->canRedirect());
        }
    }

    public function testIncorrectRedirectUrl()
    {
        $oidcService = $this->setupService();

        $request = new Request();
        $request = $request->replace([
            // data from original /authorize
            'response_type' => 'code',
            'client_id' => 'client_123',
            'state' => 'state',
            'scope' => 'scope',
            'redirect_uri' => 'https://not-correct',
            'code_challenge' => '8juoLS5oOXU8-tJzjQdAHrqrl7QF6LlnPoC6uRtPNuE',
            'code_challenge_method' => 'S256',

            // user hash, added in finishAuthorize
            'hash' => 'asdfasdfasdfasdf',
        ]);

        try {
            $oidcService->authorize($request);
            $this->fail();
        } catch (BadRequestHttpException $e) {
            // unknown client, so we cannot redirect
            $this->assertEquals('invalid_redirect_uri', $e->getPrevious()->getMessage());
            $this->assertFalse($e->getPrevious()->canRedirect());
        }
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

        $mock = Log::partialMock();
        $mock->expects('error')->once();

        $this->expectExceptionObject(new BadRequestHttpException());
        $this->expectExceptionMessage('authorization_code expected as response type');
        $oidcService->accessToken($request, '');
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

        $this->storageMock->shouldReceive('fetchAuthData')->with('0000000000000000000')->once()->andReturnNull();

        $mock = Log::partialMock();
        $mock->expects('error')->once();

        $this->expectExceptionObject(new BadRequestHttpException());
        $this->expectExceptionMessage('code not found or expired');
        $oidcService->accessToken($request, '');
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

        $mock = Log::partialMock();
        $mock->expects('error')->once();

        $this->expectExceptionObject(new BadRequestHttpException());
        $this->expectExceptionMessage('incorrect redirect uri');
        $oidcService->accessToken($request, '');
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

        $mock = Log::partialMock();
        $mock->expects('error')->once();

        $this->expectExceptionObject(new BadRequestHttpException());
        $this->expectExceptionMessage('bad challenge');
        $oidcService->accessToken($request, 'foobar');
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
            'hash' => 'asdfasdfasdf',
        ]);

        $this->storageMock->shouldReceive('fetchAuthData')->with('0000000000000000000')->once()->andReturns([
            'response_type' => 'code',
            'client_id' => 'client_123',
            'state' => 'the-state',
            'scope' => 'scope',
            'redirect_uri' => 'https://foo',
            'code_challenge' => '8juoLS5oOXU8-tJzjQdAHrqrl7QF6LlnPoC6uRtPNuE',
            'code_challenge_method' => 'S256',
            'hash' => 'asdfasdfasdf',
        ]);

        $response = $oidcService->accessToken($request, '8juoLS5oOXU8-tJzjQdAHrqrl7QF6LlnPoC6uRtPNuE');

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
                'name' => 'client123',
                'redirect_uris' => [
                    'https://foo',
                    'https://bar',
                    'https://baz'
                ]
            ],
            'client_test' => [
                'name' => 'clienttest',
                'redirect_uris' => [
                    'https://test.com',
                ]
            ]
        ]);
        $this->jwtService = new JwtService(
            config('jwt.private_key_path'),
            config('jwt.certificate_path'),
            config('jwt.iss'),
            config('jwt.aud'),
            config('jwt.exp'),
        );

        return new OidcService(
            $this->clientResolver,
            $this->storageMock,
            $this->jwtService
        );
    }
}
