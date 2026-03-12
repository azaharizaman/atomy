<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\JwtServiceInterface;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/up');

        $response->assertStatus(200);
    }

    /**
     * Test a basic API route with JWT authentication.
     */
    public function test_api_dashboard_kpis_returns_success_with_jwt(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        
        /** @var JwtServiceInterface $jwtService */
        $jwtService = app(JwtServiceInterface::class);
        $token = $jwtService->issueAccessToken((string) $user->id, (string) $user->tenant_id);

        $response = $this->getJson('/api/v1/dashboard/kpis', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        // Dashboard KPIs currently returns a stub 200 response
        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => ['total_spend', 'active_rfqs', 'pending_approvals', 'risk_alerts']]);
    }

    /**
     * Test API authentication failure.
     */
    public function test_api_dashboard_kpis_returns_unauthorized_without_token(): void
    {
        $response = $this->getJson('/api/v1/dashboard/kpis');

        $response->assertStatus(401);
    }
}
