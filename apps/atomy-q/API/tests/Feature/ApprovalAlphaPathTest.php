<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Approval;
use App\Models\ComparisonRun;
use App\Models\Rfq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Feature\Api\ApiTestCase;

final class ApprovalAlphaPathTest extends ApiTestCase
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

    private function createUser(string $tenantId): User
    {
        /** @var User $user */
        $user = User::query()->create([
            'tenant_id' => $tenantId,
            'email' => 'u-' . Str::lower((string) Str::ulid()) . '@example.com',
            'name' => 'User',
            'password_hash' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => now(),
        ]);

        return $user;
    }

    /**
     * @return array{0: User, 1: Rfq, 2: ComparisonRun, 3: Approval}
     */
    private function seedPendingApproval(User $user): array
    {
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-APP-' . Str::lower((string) Str::ulid()),
            'title' => 'Alpha RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'published',
        ]);

        $run = ComparisonRun::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'name' => 'Final',
            'description' => null,
            'idempotency_key' => null,
            'is_preview' => false,
            'created_by' => $user->id,
            'request_payload' => [],
            'matrix_payload' => [],
            'scoring_payload' => [],
            'approval_payload' => [],
            'response_payload' => [
                'snapshot' => [
                    'normalized_lines' => [['quote_submission_id' => 'x']],
                    'rfq_version' => 1,
                    'resolutions' => [],
                    'currency_meta' => [],
                ],
            ],
            'readiness_payload' => [],
            'status' => 'final',
            'version' => 1,
            'expires_at' => null,
            'discarded_at' => null,
            'discarded_by' => null,
        ]);

        $approval = Approval::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'comparison_run_id' => $run->id,
            'type' => 'comparison_approval',
            'status' => 'pending',
            'requested_by' => $user->id,
            'requested_at' => now(),
            'amount' => null,
            'currency' => null,
            'level' => 1,
            'notes' => null,
            'approved_at' => null,
            'approved_by' => null,
            'snoozed_until' => null,
        ]);

        return [$user, $rfq, $run, $approval];
    }

    public function test_index_lists_pending_approval_for_tenant(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        [, , , $approval] = $this->seedPendingApproval($user);

        $noiseRfq = Rfq::query()->create([
            'tenant_id' => $tenantId,
            'rfq_number' => 'RFQ-NOISE-' . Str::lower((string) Str::ulid()),
            'title' => 'Noise RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'published',
        ]);
        Approval::query()->create([
            'tenant_id' => $tenantId,
            'rfq_id' => $noiseRfq->id,
            'comparison_run_id' => null,
            'type' => 'comparison_approval',
            'status' => 'approved',
            'requested_by' => $user->id,
            'requested_at' => now(),
            'amount' => null,
            'currency' => null,
            'level' => 1,
            'notes' => null,
            'approved_at' => now(),
            'approved_by' => $user->id,
            'snoozed_until' => null,
        ]);

        $tenantB = (string) Str::ulid();
        $userB = $this->createUser($tenantB);
        $this->seedPendingApproval($userB);

        $response = $this->getJson(
            '/api/v1/approvals?status=pending',
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.id', $approval->id);
    }

    public function test_index_is_empty_for_tenant_with_no_approvals(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);

        $response = $this->getJson(
            '/api/v1/approvals',
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('meta.total', 0);
        $response->assertJsonPath('data', []);
    }

    public function test_show_returns_404_for_other_tenant(): void
    {
        $tenantA = (string) Str::ulid();
        $tenantB = (string) Str::ulid();
        $userA = $this->createUser($tenantA);
        $userB = $this->createUser($tenantB);
        [, , , $approval] = $this->seedPendingApproval($userA);

        $response = $this->getJson(
            '/api/v1/approvals/' . $approval->id,
            $this->authHeaders($tenantB, (string) $userB->id),
        );

        $response->assertStatus(404);
    }

    public function test_show_returns_approval_for_owning_tenant(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        [, , , $approval] = $this->seedPendingApproval($user);

        $response = $this->getJson(
            '/api/v1/approvals/' . $approval->id,
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('data.id', $approval->id);
        $response->assertJsonPath('data.status', 'pending');
    }

    public function test_reject_sets_status_rejected(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        [, , , $approval] = $this->seedPendingApproval($user);

        $otherTenantId = (string) Str::ulid();
        $otherUser = $this->createUser($otherTenantId);
        $this->postJson(
            '/api/v1/approvals/' . $approval->id . '/reject',
            ['reason' => 'Cross-tenant attempt'],
            $this->authHeaders($otherTenantId, (string) $otherUser->id),
        )->assertStatus(404);
        $this->assertSame('pending', $approval->fresh()?->status);

        $response = $this->postJson(
            '/api/v1/approvals/' . $approval->id . '/reject',
            ['reason' => 'No go'],
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('data.status', 'rejected');
        $this->assertSame('rejected', $approval->fresh()?->status);
    }

    public function test_index_filters_by_rfq_id(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        [, $rfq, , $approval] = $this->seedPendingApproval($user);

        $this->seedPendingApproval($user);

        $tenantB = (string) Str::ulid();
        $userB = $this->createUser($tenantB);
        $this->seedPendingApproval($userB);

        $response = $this->getJson(
            '/api/v1/approvals?rfq_id=' . $rfq->id . '&per_page=1',
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.id', $approval->id);
    }
}
