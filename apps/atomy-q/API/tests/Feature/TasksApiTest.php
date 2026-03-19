<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\JwtServiceInterface;
use App\Models\Project as ProjectModel;
use App\Models\ProjectAcl;
use App\Models\Task as TaskModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class TasksApiTest extends TestCase
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

    protected function withTasksEnabled(): void
    {
        $this->app['config']->set('features.tasks', true);
    }

    protected function withTasksDisabled(): void
    {
        $this->app['config']->set('features.tasks', false);
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
        return ['Authorization' => 'Bearer ' . $token];
    }

    public function test_tasks_index_returns_200_with_auth_and_feature_enabled(): void
    {
        $this->withTasksEnabled();
        $user = $this->createUser((string) Str::ulid());
        $response = $this->getJson('/api/v1/tasks', $this->authHeaders((string) $user->id, (string) $user->tenant_id));
        $response->assertStatus(200);
        $response->assertJsonPath('data', []);
    }

    public function test_tasks_index_returns_401_without_auth(): void
    {
        $this->withTasksEnabled();
        $response = $this->getJson('/api/v1/tasks');
        $response->assertStatus(401);
    }

    public function test_tasks_index_returns_404_when_feature_disabled(): void
    {
        $this->withTasksDisabled();
        $user = $this->createUser((string) Str::ulid());
        $response = $this->getJson('/api/v1/tasks', $this->authHeaders((string) $user->id, (string) $user->tenant_id));
        $response->assertStatus(404);
    }

    public function test_tasks_show_returns_404_for_cross_tenant_access(): void
    {
        $this->withTasksEnabled();
        $tenantA = (string) Str::ulid();
        $tenantB = (string) Str::ulid();
        $this->createUser($tenantA);
        $userB = $this->createUser($tenantB);

        $task = TaskModel::query()->create([
            'tenant_id' => $tenantA,
            'title' => 'Task A',
            'status' => 'pending',
            'priority' => 'medium',
        ]);

        $response = $this->getJson(
            '/api/v1/tasks/' . $task->id,
            $this->authHeaders((string) $userB->id, $tenantB)
        );
        $response->assertStatus(404);
    }

    public function test_tasks_store_returns_422_for_invalid_payload(): void
    {
        $this->withTasksEnabled();
        $user = $this->createUser((string) Str::ulid());
        $response = $this->postJson('/api/v1/tasks', [], $this->authHeaders((string) $user->id, (string) $user->tenant_id));
        $response->assertStatus(422);
    }

    public function test_tasks_show_returns_404_when_task_project_acl_denies_access(): void
    {
        $this->withTasksEnabled();
        $tenantId = (string) Str::ulid();
        $owner = $this->createUser($tenantId);
        $other = $this->createUser($tenantId);

        $project = ProjectModel::query()->create([
            'tenant_id' => $tenantId,
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
            'tenant_id' => $tenantId,
        ]);

        $task = TaskModel::query()->create([
            'tenant_id' => $tenantId,
            'title' => 'Task in project',
            'status' => 'pending',
            'priority' => 'medium',
            'project_id' => $project->id,
        ]);

        $response = $this->getJson('/api/v1/tasks/' . $task->id, $this->authHeaders((string) $other->id, $tenantId));
        $response->assertStatus(404);
    }

    public function test_tasks_show_returns_200_when_task_project_acl_allows_access(): void
    {
        $this->withTasksEnabled();
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);

        $project = ProjectModel::query()->create([
            'tenant_id' => $tenantId,
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
            'tenant_id' => $tenantId,
        ]);

        $task = TaskModel::query()->create([
            'tenant_id' => $tenantId,
            'title' => 'Task in project',
            'status' => 'pending',
            'priority' => 'medium',
            'project_id' => $project->id,
        ]);

        $response = $this->getJson('/api/v1/tasks/' . $task->id, $this->authHeaders((string) $user->id, $tenantId));
        $response->assertStatus(200);
    }
}
