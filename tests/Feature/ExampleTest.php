<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testApplication()
    {
        $response = $this->get('/');
        $response->assertStatus(200)->assertSeeText('GGD PatientId Auth Provider');
    }
}
