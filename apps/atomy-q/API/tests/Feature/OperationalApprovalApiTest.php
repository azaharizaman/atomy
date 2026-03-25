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

    /**
     * @return array{0: string, 1: string} [userId, tenantId]
     */
    private function seedUserForTenant(string $tenantId): array
    {
        $userId = (string) Str::ulid();
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

        return [$userId, $tenantId];
    }

    private function seedApprovalPolicyForTenant(string $tenantId): void
    {
        DB::table('policy_definitions')->insert([
            'id' => (string) Str::ulid(),
            'tenant_id' => Str::lower($tenantId),
            'policy_id' => 'pol-demo',
            'policy_version' => '1',
            'payload' => json_encode([
                'id' => 'pol-demo',
                'version' => '1',
                'tenant_id' => Str::lower($tenantId),
                'kind' => 'authorization',
                'strategy' => 'collect_all',
                'rules' => [
                    [
                        'id' => 'rule.deny.context',
                        'priority' => 200,
                        'outcome' => 'deny',
                        'reason_code' => 'approval.start.denied',
                        'conditions' => [
                            'mode' => 'all',
                            'items' => [
                                ['field' => 'deny_start', 'operator' => 'eq', 'value' => true],
                            ],
                        ],
                    ],
                    [
                        'id' => 'rule.allow.default',
                        'priority' => 100,
                        'outcome' => 'allow',
                        'reason_code' => 'approval.start.allowed',
                        'conditions' => [
                            'mode' => 'all',
                            'items' => [
                                ['field' => 'action', 'operator' => 'eq', 'value' => 'operational_approval.start'],
                            ],
                        ],
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_start_operational_approval_returns_201_when_template_exists(): void
    {
        $tenantId = (string) Str::ulid();
        $templateId = (string) Str::ulid();

        [$userId] = $this->seedUserForTenant($tenantId);
        $this->seedApprovalPolicyForTenant($tenantId);

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
        $data = $response->json('data');
        $this->assertIsString($data['instance_id']);
        $this->assertNotEmpty($data['instance_id']);
        $this->assertIsString($data['workflow_instance_id']);
        $this->assertNotEmpty($data['workflow_instance_id']);

        $this->assertDatabaseHas('operational_approval_workflows', [
            'id' => $data['workflow_instance_id'],
            'tenant_id' => $tenantId,
            'operational_approval_instance_id' => $data['instance_id'],
            'workflow_definition_id' => 'wf-demo',
            'subject_type' => 'purchase_order',
            'subject_id' => 'po-99',
            'current_state' => 'pending',
        ]);
    }

    public function test_start_operational_approval_returns_403_when_policy_denies(): void
    {
        $tenantId = (string) Str::ulid();
        $templateId = (string) Str::ulid();

        [$userId] = $this->seedUserForTenant($tenantId);
        $this->seedApprovalPolicyForTenant($tenantId);

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
                'subject_id' => 'po-100-denied',
                'context' => ['deny_start' => true],
            ],
            $this->authHeaders($userId, $tenantId),
        );

        $response->assertStatus(403);
        $this->assertDatabaseMissing('operational_approval_instances', [
            'tenant_id' => $tenantId,
            'subject_id' => 'po-100-denied',
        ]);
    }

    public function test_show_operational_approval_returns_real_sla_snapshot(): void
    {
        $tenantId = (string) Str::ulid();
        $templateId = (string) Str::ulid();

        [$userId] = $this->seedUserForTenant($tenantId);
        $this->seedApprovalPolicyForTenant($tenantId);

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

        $startResponse = $this->postJson(
            '/api/v1/operational-approvals/instances',
            [
                'subject_type' => 'purchase_order',
                'subject_id' => 'po-100-sla',
                'context' => ['note' => 'sla snapshot'],
            ],
            $this->authHeaders($userId, $tenantId),
        );

        $startResponse->assertStatus(201);
        $instanceId = (string) $startResponse->json('data.instance_id');

        $showResponse = $this->getJson(
            '/api/v1/operational-approvals/instances/' . $instanceId,
            $this->authHeaders($userId, $tenantId),
        );

        $showResponse->assertOk();
        $data = $showResponse->json('data');
        $this->assertIsString($data['due_at']);
        $this->assertNotEmpty($data['due_at']);
        $this->assertIsInt($data['seconds_remaining']);
        $this->assertGreaterThanOrEqual(0, $data['seconds_remaining']);
    }

    public function test_list_operational_approvals_returns_tenant_scoped_rows(): void
    {
        $tenantId = (string) Str::ulid();
        $templateId = (string) Str::ulid();

        [$userId] = $this->seedUserForTenant($tenantId);
        $this->seedApprovalPolicyForTenant($tenantId);

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

        $this->postJson(
            '/api/v1/operational-approvals/instances',
            [
                'subject_type' => 'purchase_order',
                'subject_id' => 'po-list-1',
                'context' => ['note' => 'list'],
            ],
            $this->authHeaders($userId, $tenantId),
        )->assertStatus(201);

        $this->postJson(
            '/api/v1/operational-approvals/instances',
            [
                'subject_type' => 'purchase_order',
                'subject_id' => 'po-list-2',
                'context' => ['note' => 'list'],
            ],
            $this->authHeaders($userId, $tenantId),
        )->assertStatus(201);

        $response = $this->getJson(
            '/api/v1/operational-approvals/instances',
            $this->authHeaders($userId, $tenantId),
        );

        $response->assertOk();
        $data = $response->json('data');
        $meta = $response->json('meta');
        $this->assertCount(2, $data);
        $this->assertSame($tenantId, $data[0]['tenant_id']);
        $this->assertSame('purchase_order', $data[0]['subject_type']);
        $this->assertArrayHasKey('due_at', $data[0]);
        $this->assertArrayHasKey('seconds_remaining', $data[0]);
        $this->assertSame(2, $meta['total']);
        $this->assertSame(25, $meta['per_page']);
        $this->assertSame(1, $meta['current_page']);
        $this->assertSame(1, $meta['last_page']);
    }

    public function test_store_decision_updates_persisted_workflow_state_for_tenant(): void
    {
        $tenantId = (string) Str::ulid();
        $templateId = (string) Str::ulid();

        [$userId] = $this->seedUserForTenant($tenantId);
        $this->seedApprovalPolicyForTenant($tenantId);

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

        $startResponse = $this->postJson(
            '/api/v1/operational-approvals/instances',
            [
                'subject_type' => 'purchase_order',
                'subject_id' => 'po-100',
                'context' => ['note' => 'approve me'],
            ],
            $this->authHeaders($userId, $tenantId),
        );

        $startResponse->assertStatus(201);
        $instanceId = (string) $startResponse->json('data.instance_id');
        $workflowInstanceId = (string) $startResponse->json('data.workflow_instance_id');

        $decisionResponse = $this->postJson(
            '/api/v1/operational-approvals/instances/' . $instanceId . '/decisions',
            [
                'decision' => 'approve',
                'comment' => 'approved for release',
            ],
            $this->authHeaders($userId, $tenantId),
        );

        $decisionResponse->assertOk();

        $this->assertDatabaseHas('operational_approval_workflows', [
            'id' => $workflowInstanceId,
            'tenant_id' => $tenantId,
            'current_state' => 'approved',
            'last_actor_principal_id' => $userId,
            'last_comment' => 'approved for release',
        ]);
    }

    public function test_store_decision_persists_attachment_storage_key_on_comment(): void
    {
        $tenantId = (string) Str::ulid();
        $templateId = (string) Str::ulid();

        [$userId] = $this->seedUserForTenant($tenantId);
        $this->seedApprovalPolicyForTenant($tenantId);

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

        $startResponse = $this->postJson(
            '/api/v1/operational-approvals/instances',
            [
                'subject_type' => 'purchase_order',
                'subject_id' => 'po-attachment',
                'context' => ['note' => 'attachment'],
            ],
            $this->authHeaders($userId, $tenantId),
        );

        $startResponse->assertStatus(201);
        $instanceId = (string) $startResponse->json('data.instance_id');

        $decisionResponse = $this->postJson(
            '/api/v1/operational-approvals/instances/' . $instanceId . '/decisions',
            [
                'decision' => 'approve',
                'comment' => 'approved with attachment',
                'attachment_storage_key' => 'approval-ops/attachment-1.pdf',
            ],
            $this->authHeaders($userId, $tenantId),
        );

        $decisionResponse->assertOk();

        $this->assertDatabaseHas('operational_approval_comments', [
            'tenant_id' => $tenantId,
            'instance_id' => $instanceId,
            'author_principal_id' => $userId,
            'body' => 'approved with attachment',
            'attachment_storage_key' => 'approval-ops/attachment-1.pdf',
        ]);
    }

    public function test_store_decision_returns_500_when_workflow_row_is_missing(): void
    {
        $tenantId = (string) Str::ulid();
        $templateId = (string) Str::ulid();

        [$userId] = $this->seedUserForTenant($tenantId);
        $this->seedApprovalPolicyForTenant($tenantId);

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

        $startResponse = $this->postJson(
            '/api/v1/operational-approvals/instances',
            [
                'subject_type' => 'purchase_order',
                'subject_id' => 'po-101',
                'context' => ['note' => 'missing workflow row'],
            ],
            $this->authHeaders($userId, $tenantId),
        );

        $startResponse->assertStatus(201);
        $instanceId = (string) $startResponse->json('data.instance_id');
        $workflowInstanceId = (string) $startResponse->json('data.workflow_instance_id');

        DB::table('operational_approval_workflows')
            ->where('id', $workflowInstanceId)
            ->delete();

        $decisionResponse = $this->postJson(
            '/api/v1/operational-approvals/instances/' . $instanceId . '/decisions',
            [
                'decision' => 'approve',
                'comment' => 'should fail',
            ],
            $this->authHeaders($userId, $tenantId),
        );

        $decisionResponse->assertStatus(500);
        $this->assertDatabaseHas('operational_approval_instances', [
            'id' => $instanceId,
            'tenant_id' => $tenantId,
            'status' => 'pending',
        ]);
    }

    public function test_get_instance_returns_sla_fields_for_tenant(): void
    {
        $tenantId = (string) Str::ulid();
        $templateId = (string) Str::ulid();
        $policyId = 'pol-sla-allow';
        $policyVersion = '1';

        [$userId] = $this->seedUserForTenant($tenantId);

        DB::table('policy_definitions')->insert([
            'id' => (string) Str::ulid(),
            // PolicyEngine normalizes tenant IDs to lowercase in the request VO, so keep the registry table canonical.
            'tenant_id' => Str::lower($tenantId),
            'policy_id' => $policyId,
            'policy_version' => $policyVersion,
            'payload' => json_encode([
                'id' => $policyId,
                'version' => $policyVersion,
                'tenant_id' => Str::lower($tenantId),
                'kind' => 'authorization',
                'strategy' => 'collect_all',
                'rules' => [
                    [
                        'id' => 'allow.start',
                        'priority' => 100,
                        'outcome' => 'allow',
                        'reason_code' => 'allow.start',
                        'conditions' => [
                            'mode' => 'all',
                            'items' => [
                                ['field' => 'action', 'operator' => 'exists'],
                            ],
                        ],
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('operational_approval_templates')->insert([
            'id' => $templateId,
            'tenant_id' => $tenantId,
            'subject_type' => 'purchase_order',
            'workflow_definition_id' => 'wf-demo',
            'policy_id' => $policyId,
            'policy_version' => $policyVersion,
            'template_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $startResponse = $this->postJson(
            '/api/v1/operational-approvals/instances',
            [
                'subject_type' => 'purchase_order',
                'subject_id' => 'po-200',
                'context' => ['note' => 'sla test'],
            ],
            $this->authHeaders($userId, $tenantId),
        );

        $startResponse->assertStatus(201);
        $instanceId = (string) $startResponse->json('data.instance_id');

        $showResponse = $this->getJson(
            '/api/v1/operational-approvals/instances/' . $instanceId,
            $this->authHeaders($userId, $tenantId),
        );

        $showResponse->assertOk();
        $data = $showResponse->json('data');
        $this->assertIsString($data['due_at']);
        $this->assertNotEmpty($data['due_at']);
        $this->assertIsInt($data['seconds_remaining']);
        $this->assertGreaterThanOrEqual(0, $data['seconds_remaining']);
    }

    public function test_get_instance_returns_404_for_other_tenant(): void
    {
        $tenantA = (string) Str::ulid();
        $tenantB = (string) Str::ulid();
        $instanceId = (string) Str::ulid();
        $templateId = (string) Str::ulid();

        [$userA] = $this->seedUserForTenant($tenantA);

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
