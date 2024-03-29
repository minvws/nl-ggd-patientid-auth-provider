<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Oidc;

use App\Services\Oidc\ArrayClientResolver;
use App\Services\Oidc\Client;
use PHPUnit\Framework\TestCase;

class ArrayClientResolverTest extends TestCase
{
    public function testResolver()
    {
        $resolver = new ArrayClientResolver([
            'client_123' => [
                'name' => 'client123',
                'redirect_uris' => [
                    'https://foo',
                    'https://bar',
                    'https://baz'
                ]
            ],
            'client_test' => [
                'name' => 'clientest',
                'redirect_uris' => [
                    'https://test.com',
                ]
            ]
        ]);

        $this->assertFalse($resolver->exists('not-existing'));
        $this->assertTrue($resolver->exists('client_test'));
        $this->assertTrue($resolver->exists('client_123'));

        $this->assertInstanceOf(Client::class, $resolver->resolve('client_123'));
        $this->assertNull($resolver->resolve('not-existing'));

        $client = $resolver->resolve('client_123');
        $this->assertCount(3, $client->getRedirectUris());
        $this->assertEquals('client_123', $client->getClientId());
        $this->assertEquals('client123', $client->getName());
    }
}
