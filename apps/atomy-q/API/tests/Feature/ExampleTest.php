<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/up');

        $response->assertStatus(200);
    }

    /**
     * Test a basic API route.
     */
    public function test_api_login_returns_method_not_allowed_for_get(): void
    {
        // Simple smoke test to check API routing/middleware
        $response = $this->getJson('/api/v1/auth/login');

        $response->assertStatus(405);
    }
}
