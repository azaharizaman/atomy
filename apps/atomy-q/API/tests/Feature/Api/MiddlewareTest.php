<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Services\JwtService;
use Tests\TestCase;

final class MiddlewareTest extends TestCase
{
    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/dashboard/kpis');

        $response->assertStatus(401);
        $response->assertJsonFragment(['error' => 'Authentication required']);
    }

    public function test_rejects_invalid_token(): void
    {
        $response = $this->getJson('/api/v1/dashboard/kpis', [
            'Authorization' => 'Bearer invalid-token',
        ]);

        $response->assertStatus(401);
        $response->assertJsonFragment(['error' => 'Invalid or expired token']);
    }

    public function test_rejects_missing_tenant_context(): void
    {
        $jwt = app(JwtService::class);
        $token = $jwt->issueAccessToken('user-1', '');

        $response = $this->getJson('/api/v1/dashboard/kpis', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(403);
        $response->assertJsonFragment(['error' => 'Tenant context required']);
    }
}
