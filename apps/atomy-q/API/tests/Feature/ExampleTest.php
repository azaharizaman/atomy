<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\JwtServiceInterface;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = parent::createApplication();

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        return $app;
    }

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
        $user = $this->createUser();
        
        /** @var JwtServiceInterface $jwtService */
        $jwtService = app(JwtServiceInterface::class);
        $token = $jwtService->issueAccessToken((string) $user->id, (string) $user->tenant_id);

        $response = $this->getJson('/api/v1/dashboard/kpis', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        // Dashboard KPIs currently returns a stub 200 response
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'active_rfqs',
            'pending_approvals',
            'total_savings',
            'avg_cycle_time_days',
        ]);
    }

    /**
     * Test API authentication failure.
     */
    public function test_api_dashboard_kpis_returns_unauthorized_without_token(): void
    {
        $response = $this->getJson('/api/v1/dashboard/kpis');

        $response->assertStatus(401);
    }

    private function createUser(): User
    {
        $tenantId = (string) Str::ulid();

        /** @var User $user */
        $user = User::query()->create([
            'tenant_id' => $tenantId,
            'email' => 'user-' . Str::lower((string) Str::ulid()) . '@example.com',
            'name' => 'Example Test User',
            'password_hash' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => now(),
        ]);

        return $user;
    }
}
