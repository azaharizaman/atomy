<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\JwtServiceInterface;
use App\Models\Project as ProjectModel;
use App\Models\Rfq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProjectsApiTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication(): \Illuminate\Foundation\Application
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

    protected function withProjectsEnabled(): void
    {
        $this->app['config']->set('features.projects', true);
    }

    protected function withProjectsDisabled(): void
    {
        $this->app['config']->set('features.projects', false);
    }

    private function createUser(string $tenantId): User
    {
        /** @var User $user */
        $user = User::query()->create([
            'tenant_id' => $tenantId,
            'email' => 'user-' . Str::lower((string) Str::ulid()) . '@example.com',
            'name' => 'Test User',
            'password_hash' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => now(),
        ]);
        return $user;
    }

    private function authHeaders(string $userId, string $tenantId): array
    {
        $jwt = app(JwtServiceInterface::class);
        $token = $jwt->issueAccessToken($userId, $tenantId);
        return [
            'Authorization' => 'Bearer ' . $token,
            'Idempotency-Key' => (string) Str::uuid(),
        ];
    }

    public function test_projects_index_returns_200_with_auth_and_feature_enabled(): void
    {
        $this->withProjectsEnabled();
        $user = $this->createUser((string) Str::ulid());
        $response = $this->getJson('/api/v1/projects', $this->authHeaders((string) $user->id, (string) $user->tenant_id));
        $response->assertStatus(200);
        $response->assertJsonPath('data', []);
    }

    public function test_projects_index_returns_401_without_auth(): void
    {
        $this->withProjectsEnabled();
        $response = $this->getJson('/api/v1/projects');
        $response->assertStatus(401);
    }

    public function test_projects_index_returns_404_when_feature_disabled(): void
    {
        $this->withProjectsDisabled();
        $user = $this->createUser((string) Str::ulid());
        $response = $this->getJson('/api/v1/projects', $this->authHeaders((string) $user->id, (string) $user->tenant_id));
        $response->assertStatus(404);
    }

    public function test_projects_show_returns_404_for_cross_tenant_access(): void
    {
        $this->withProjectsEnabled();
        $tenantA = (string) Str::ulid();
        $tenantB = (string) Str::ulid();
        $this->createUser($tenantA);
        $userB = $this->createUser($tenantB);

        $project = ProjectModel::query()->create([
            'tenant_id' => $tenantA,
            'name' => 'Project A',
            'client_id' => 'client-1',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'project_manager_id' => (string) Str::ulid(),
            'status' => 'planning',
        ]);

        $response = $this->getJson(
            '/api/v1/projects/' . $project->id,
            $this->authHeaders((string) $userB->id, $tenantB)
        );
        $response->assertStatus(404);
    }

    public function test_projects_store_returns_422_for_invalid_payload(): void
    {
        $this->withProjectsEnabled();
        $user = $this->createUser((string) Str::ulid());
        $response = $this->postJson('/api/v1/projects', [], $this->authHeaders((string) $user->id, (string) $user->tenant_id));
        $response->assertStatus(422);
    }

    public function test_project_budget_sums_rfq_estimated_value_and_signed_off_awards(): void
    {
        $this->withProjectsEnabled();
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);

        $project = ProjectModel::query()->create([
            'tenant_id' => $tenantId,
            'name' => 'Budget project',
            'client_id' => 'client-1',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'project_manager_id' => (string) Str::ulid(),
            'status' => 'planning',
        ]);

        $rfq = Rfq::query()->create([
            'tenant_id' => $tenantId,
            'rfq_number' => 'RFQ-1',
            'title' => 'RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'awarded',
            'project_id' => $project->id,
            'estimated_value' => 1000,
        ]);

        DB::table('awards')->insert([
            'id' => (string) Str::ulid(),
            'tenant_id' => $tenantId,
            'rfq_id' => $rfq->id,
            'comparison_run_id' => null,
            'vendor_id' => (string) Str::ulid(),
            'status' => 'signed_off',
            'amount' => 800,
            'currency' => 'USD',
            'split_details' => json_encode([], JSON_THROW_ON_ERROR),
            'protest_id' => null,
            'signoff_at' => now(),
            'signed_off_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson(
            '/api/v1/projects/' . $project->id . '/budget',
            $this->authHeaders((string) $user->id, (string) $user->tenant_id)
        );

        $response->assertStatus(200);
        $response->assertJsonPath('data.project_id', $project->id);
        $response->assertJsonPath('data.budgeted', 1000);
        $response->assertJsonPath('data.actual', 800);
        $response->assertJsonPath('data.currency', 'USD');
    }
}
