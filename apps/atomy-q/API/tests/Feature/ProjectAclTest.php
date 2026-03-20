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

class ProjectAclTest extends TestCase
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
        $app['config']->set('features.projects', true);
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
        return ['Authorization' => 'Bearer ' . $token];
    }

    public function test_get_acl_returns_roles_and_update_acl_persists(): void
    {
        $user = $this->createUser();
        $project = ProjectModel::query()->create([
            'tenant_id' => $user->tenant_id,
            'name' => 'ACL project',
            'client_id' => 'c1',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'project_manager_id' => (string) Str::ulid(),
            'status' => 'planning',
        ]);

        $getResponse = $this->getJson('/api/v1/projects/' . $project->id . '/acl', $this->authHeaders($user));
        $getResponse->assertStatus(200);
        $getResponse->assertJsonPath('data.roles', []);

        ProjectAcl::query()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'tenant_id' => $user->tenant_id,
        ]);

        $putResponse = $this->putJson('/api/v1/projects/' . $project->id . '/acl', [
            'roles' => [
                ['user_id' => $user->id, 'role' => 'owner'],
            ],
        ], $this->authHeaders($user));
        $putResponse->assertStatus(200);
        $putResponse->assertJsonPath('data.roles.0.user_id', $user->id);
        $putResponse->assertJsonPath('data.roles.0.role', 'owner');

        $this->assertDatabaseHas('project_acl', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);
    }

    public function test_rfq_with_project_id_denied_when_user_not_in_acl(): void
    {
        $owner = $this->createUser();
        $otherUser = $this->createUser();
        $otherUser->tenant_id = $owner->tenant_id;
        $otherUser->save();

        $project = ProjectModel::query()->create([
            'tenant_id' => $owner->tenant_id,
            'name' => 'ACL project',
            'client_id' => 'c1',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'project_manager_id' => (string) Str::ulid(),
            'status' => 'planning',
        ]);
        ProjectAcl::query()->create([
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'role' => 'owner',
            'tenant_id' => $owner->tenant_id,
        ]);

        $rfq = Rfq::query()->create([
            'tenant_id' => $owner->tenant_id,
            'rfq_number' => 'RFQ-1',
            'title' => 'RFQ in project',
            'owner_id' => $owner->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'draft',
            'project_id' => $project->id,
        ]);

        $response = $this->getJson('/api/v1/rfqs/' . $rfq->id, $this->authHeaders($otherUser));
        $response->assertStatus(404);
    }

    public function test_rfq_with_project_id_allowed_when_user_in_acl(): void
    {
        $user = $this->createUser();
        $project = ProjectModel::query()->create([
            'tenant_id' => $user->tenant_id,
            'name' => 'ACL project',
            'client_id' => 'c1',
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
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-1',
            'title' => 'RFQ in project',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'draft',
            'project_id' => $project->id,
        ]);

        $response = $this->getJson('/api/v1/rfqs/' . $rfq->id, $this->authHeaders($user));
        $response->assertStatus(200);
    }

    public function test_client_stakeholder_role_can_view_project_scoped_rfq(): void
    {
        $user = $this->createUser();
        $project = ProjectModel::query()->create([
            'tenant_id' => $user->tenant_id,
            'name' => 'ACL project',
            'client_id' => 'c1',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'project_manager_id' => (string) Str::ulid(),
            'status' => 'planning',
        ]);
        ProjectAcl::query()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => 'client_stakeholder',
            'tenant_id' => $user->tenant_id,
        ]);
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-2',
            'title' => 'RFQ in project',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'draft',
            'project_id' => $project->id,
        ]);

        $response = $this->getJson('/api/v1/rfqs/' . $rfq->id, $this->authHeaders($user));
        $response->assertStatus(200);
    }
}
