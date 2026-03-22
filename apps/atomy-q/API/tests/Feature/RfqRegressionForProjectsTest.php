<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\JwtServiceInterface;
use App\Models\Project as ProjectModel;
use App\Models\ProjectAcl;
use App\Models\Rfq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class RfqRegressionForProjectsTest extends TestCase
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

    private function createUser(): User
    {
        $tenantId = (string) Str::ulid();
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

    private function authHeaders(User $user): array
    {
        $jwt = app(JwtServiceInterface::class);
        $token = $jwt->issueAccessToken((string) $user->id, (string) $user->tenant_id);
        return [
            'Authorization' => 'Bearer ' . $token,
            'Idempotency-Key' => (string) Str::uuid(),
        ];
    }

    public function test_create_rfq_without_project_id_succeeds_and_response_has_project_id_key(): void
    {
        $user = $this->createUser();
        $payload = [
            'title' => 'Regression RFQ without project',
            'description' => 'Optional project_id not sent',
            'submission_deadline' => now()->addDays(14)->toAtomString(),
        ];
        $response = $this->postJson('/api/v1/rfqs', $payload, $this->authHeaders($user));
        $response->assertStatus(201);
        $response->assertJsonStructure(['data' => ['id', 'rfq_number', 'title', 'status', 'project_id']]);
        $data = $response->json('data');
        $this->assertArrayHasKey('project_id', $data);
        $this->assertNull($data['project_id']);
    }

    public function test_update_rfq_without_changing_project_id_preserves_existing_behaviour(): void
    {
        $user = $this->createUser();
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-2026-9001',
            'title' => 'Original title',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'draft',
            'project_id' => null,
        ]);
        $response = $this->putJson('/api/v1/rfqs/' . $rfq->id, ['title' => 'Updated title'], $this->authHeaders($user));
        $response->assertStatus(200);
        $response->assertJsonPath('data.title', 'Updated title');
        $rfq->refresh();
        $this->assertNull($rfq->project_id);
    }

    public function test_create_rfq_with_project_id_when_projects_enabled_is_accepted(): void
    {
        $this->app['config']->set('features.projects', true);
        $user = $this->createUser();
        $project = ProjectModel::query()->create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Regression project',
            'client_id' => 'client-1',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'project_manager_id' => (string) Str::ulid(),
            'status' => 'planning',
        ]);
        $payload = [
            'title' => 'RFQ linked to project',
            'project_id' => $project->id,
            'submission_deadline' => now()->addDays(14)->toAtomString(),
        ];
        $response = $this->postJson('/api/v1/rfqs', $payload, $this->authHeaders($user));
        $response->assertStatus(201);
        $response->assertJsonPath('data.project_id', $project->id);
    }

    public function test_rfq_index_filters_by_project_id(): void
    {
        $user = $this->createUser();
        $project = ProjectModel::query()->create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Filter test project',
            'client_id' => 'client-1',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'project_manager_id' => (string) Str::ulid(),
            'status' => 'planning',
        ]);
        ProjectAcl::query()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => 'viewer',
            'tenant_id' => $user->tenant_id,
        ]);
        Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-A',
            'title' => 'RFQ in project',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'draft',
            'project_id' => $project->id,
        ]);
        Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-B',
            'title' => 'RFQ without project',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'draft',
            'project_id' => null,
        ]);
        $response = $this->getJson('/api/v1/rfqs?project_id=' . urlencode($project->id), $this->authHeaders($user));
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame($project->id, $data[0]['project_id']);
    }
}
