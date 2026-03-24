<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\JwtServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

final class OperationalApprovalApiTest extends TestCase
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

    private function authHeaders(string $userId, string $tenantId): array
    {
        $jwt = app(JwtServiceInterface::class);
        $token = $jwt->issueAccessToken($userId, $tenantId);

        return [
            'Authorization' => 'Bearer ' . $token,
            'Idempotency-Key' => (string) Str::uuid(),
        ];
    }

    public function test_start_operational_approval_returns_201_when_template_exists(): void
    {
        $tenantId = (string) Str::ulid();
        $userId = (string) Str::ulid();
        $templateId = (string) Str::ulid();

        DB::table('users')->insert([
            'id' => $userId,
            'tenant_id' => $tenantId,
            'email' => 'oa-' . Str::lower((string) Str::ulid()) . '@example.com',
            'name' => 'OA User',
            'password_hash' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('operational_approval_templates')->insert([
            'id' => $templateId,
            'tenant_id' => $tenantId,
            'subject_type' => 'purchase_order',
            'workflow_definition_id' => 'wf-demo',
            'policy_id' => 'pol-demo',
            'policy_version' => '1',
            'template_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson(
            '/api/v1/operational-approvals/instances',
            [
                'subject_type' => 'purchase_order',
                'subject_id' => 'po-99',
                'context' => ['note' => 'unit test'],
            ],
            $this->authHeaders($userId, $tenantId),
        );

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'instance_id',
                'workflow_instance_id',
            ],
        ]);
    }

    public function test_get_instance_returns_404_for_other_tenant(): void
    {
        $tenantA = (string) Str::ulid();
        $tenantB = (string) Str::ulid();
        $userA = (string) Str::ulid();
        $instanceId = (string) Str::ulid();
        $templateId = (string) Str::ulid();

        DB::table('users')->insert([
            'id' => $userA,
            'tenant_id' => $tenantA,
            'email' => 'oa2-' . Str::lower((string) Str::ulid()) . '@example.com',
            'name' => 'OA User',
            'password_hash' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('operational_approval_templates')->insert([
            'id' => $templateId,
            'tenant_id' => $tenantB,
            'subject_type' => 'x',
            'workflow_definition_id' => 'wf',
            'policy_id' => 'p',
            'policy_version' => '1',
            'template_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('operational_approval_instances')->insert([
            'id' => $instanceId,
            'tenant_id' => $tenantB,
            'template_id' => $templateId,
            'workflow_instance_id' => 'wf-i',
            'subject_type' => 'x',
            'subject_id' => '1',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson(
            '/api/v1/operational-approvals/instances/' . $instanceId,
            $this->authHeaders($userA, $tenantA),
        );

        $response->assertStatus(404);
    }
}
