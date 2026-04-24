<?php

declare(strict_types=1);

namespace Tests\Feature;

use DateTimeImmutable;
use Nexus\IntelligenceOperations\DTOs\AiCapabilityStatus;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Nexus\IntelligenceOperations\DTOs\AiStatusSnapshot;
use App\Adapters\Ai\Contracts\AiRuntimeStatusInterface;
use App\Adapters\Ai\Contracts\ComparisonAwardAiClientInterface;
use App\Models\Approval;
use App\Models\ComparisonRun;
use App\Models\DecisionTrailEntry;
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
     * @param array<string, AiCapabilityStatus> $capabilityStatuses
     */
    private function bindAiRuntimeStatus(array $capabilityStatuses): void
    {
        $snapshot = new AiStatusSnapshot(
            mode: AiStatusSchema::MODE_PROVIDER,
            globalHealth: AiStatusSchema::HEALTH_DEGRADED,
            capabilityDefinitions: [],
            capabilityStatuses: $capabilityStatuses,
            endpointGroupHealthSnapshots: [],
            reasonCodes: ['provider_unavailable'],
            generatedAt: new DateTimeImmutable('2026-04-24T11:00:00+08:00'),
        );

        $this->app->instance(AiRuntimeStatusInterface::class, new readonly class($snapshot) implements AiRuntimeStatusInterface {
            public function __construct(
                private AiStatusSnapshot $snapshot,
            ) {
            }

            public function snapshot(): AiStatusSnapshot
            {
                return $this->snapshot;
            }

            public function capabilityStatus(string $featureKey): ?AiCapabilityStatus
            {
                return $this->snapshot->capabilityStatuses[$featureKey] ?? null;
            }

            public function providerName(): string
            {
                return 'openrouter';
            }
        });
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

        $summaryResponse = $this->getJson(
            '/api/v1/approvals/' . $approval->id . '/summary',
            $this->authHeaders($tenantB, (string) $userB->id),
        );
        $summaryResponse->assertStatus(404);
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

    public function test_summary_returns_provider_payload_for_approval(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        [, , $run, $approval] = $this->seedPendingApproval($user);

        $this->bindAiRuntimeStatus([
            'approval_ai_summary' => new AiCapabilityStatus(
                featureKey: 'approval_ai_summary',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_GOVERNANCE_INTELLIGENCE,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_COMPARISON_AWARD,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                messageKey: 'ai.approval_ai_summary.available',
                status: AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
                available: true,
                reasonCodes: [],
                operatorCritical: true,
                diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
            ),
        ]);
        $comparisonAwardClient = $this->createMock(ComparisonAwardAiClientInterface::class);
        $comparisonAwardClient->expects($this->once())
            ->method('approvalSummary')
            ->with($this->callback(function (array $payload) use ($tenantId, $approval, $run): bool {
                return ($payload['tenant_id'] ?? null) === $tenantId
                    && ($payload['approval_id'] ?? null) === $approval->id
                    && ($payload['comparison_run_id'] ?? null) === $run->id;
            }))
            ->willReturn([
                'headline' => 'Approval can proceed with the frozen comparison evidence.',
                'risk_flags' => ['variance_with_policy'],
            ]);
        $comparisonAwardClient->expects($this->never())->method('comparisonOverlay');
        $comparisonAwardClient->expects($this->never())->method('awardGuidance');
        $comparisonAwardClient->expects($this->never())->method('awardDebriefDraft');
        $this->app->instance(ComparisonAwardAiClientInterface::class, $comparisonAwardClient);

        $response = $this->getJson(
            '/api/v1/approvals/' . $approval->id . '/summary',
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('data.ai_summary.feature_key', 'approval_ai_summary');
        $response->assertJsonPath('data.ai_summary.available', true);
        $response->assertJsonPath('data.ai_summary.payload.headline', 'Approval can proceed with the frozen comparison evidence.');
        $response->assertJsonPath('data.ai_summary.provenance.source', 'provider');

        $run = $run->fresh();
        $storedSummary = $run?->response_payload['ai_artifacts']['approval_summary'][$approval->id] ?? null;
        $this->assertIsArray($storedSummary);
        $this->assertSame('approval_ai_summary', $storedSummary['feature_key'] ?? null);
        $this->assertSame(true, $storedSummary['available'] ?? null);

        $entry = DecisionTrailEntry::query()
            ->where('tenant_id', $tenantId)
            ->where('comparison_run_id', $run->id)
            ->where('event_type', 'approval_ai_summary_generated:' . $approval->id)
            ->first();
        $this->assertNotNull($entry);

        $trailResponse = $this->getJson(
            '/api/v1/decision-trail/' . $entry->id,
            $this->authHeaders($tenantId, (string) $user->id),
        );
        $trailResponse->assertOk();
        $trailResponse->assertJsonPath('data.metadata.artifact_origin', 'provider_drafted');
        $trailResponse->assertJsonPath('data.metadata.artifact_kind', 'approval_ai_summary');
        $trailResponse->assertJsonPath('data.metadata.artifact_subject_id', $approval->id);
        $trailResponse->assertJsonPath('data.metadata.provenance.source', 'provider');
    }

    public function test_summary_returns_persisted_artifact_when_runtime_is_unavailable(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        [, , $run, $approval] = $this->seedPendingApproval($user);

        $run->response_payload = array_replace_recursive((array) $run->response_payload, [
            'ai_artifacts' => [
                'approval_summary' => [
                    (string) $approval->id => [
                        'feature_key' => 'approval_ai_summary',
                        'capability_group' => AiStatusSchema::CAPABILITY_GROUP_GOVERNANCE_INTELLIGENCE,
                        'available' => true,
                        'status' => AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
                        'manual_continuity' => 'available',
                        'payload' => ['headline' => 'Persisted summary'],
                        'provenance' => [
                            'source' => 'provider',
                            'provider_name' => 'openrouter',
                            'endpoint_group' => AiStatusSchema::ENDPOINT_GROUP_COMPARISON_AWARD,
                        ],
                    ],
                ],
            ],
        ]);
        $run->save();

        $this->bindAiRuntimeStatus([
            'approval_ai_summary' => new AiCapabilityStatus(
                featureKey: 'approval_ai_summary',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_GOVERNANCE_INTELLIGENCE,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_COMPARISON_AWARD,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                messageKey: 'ai.approval_ai_summary.unavailable',
                status: AiStatusSchema::CAPABILITY_STATUS_UNAVAILABLE,
                available: false,
                reasonCodes: ['provider_unavailable'],
                operatorCritical: true,
                diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
            ),
        ]);

        $comparisonAwardClient = $this->createMock(ComparisonAwardAiClientInterface::class);
        $comparisonAwardClient->expects($this->never())->method('approvalSummary');
        $comparisonAwardClient->expects($this->never())->method('comparisonOverlay');
        $comparisonAwardClient->expects($this->never())->method('awardGuidance');
        $comparisonAwardClient->expects($this->never())->method('awardDebriefDraft');
        $this->app->instance(ComparisonAwardAiClientInterface::class, $comparisonAwardClient);

        $response = $this->getJson(
            '/api/v1/approvals/' . $approval->id . '/summary',
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('data.ai_summary.available', true);
        $response->assertJsonPath('data.ai_summary.payload.headline', 'Persisted summary');
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

    public function test_approve_keeps_progression_working_when_ai_summary_is_unavailable(): void
    {
        $this->bindAiRuntimeStatus([
            'approval_ai_summary' => new AiCapabilityStatus(
                featureKey: 'approval_ai_summary',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_GOVERNANCE_INTELLIGENCE,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_COMPARISON_AWARD,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_UNAVAILABLE_MESSAGE,
                messageKey: 'ai.approval_ai_summary.unavailable',
                status: AiStatusSchema::CAPABILITY_STATUS_UNAVAILABLE,
                available: false,
                reasonCodes: ['provider_unavailable'],
                operatorCritical: true,
                diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
            ),
        ]);
        $comparisonAwardClient = $this->createMock(ComparisonAwardAiClientInterface::class);
        $comparisonAwardClient->expects($this->never())->method('approvalSummary');
        $comparisonAwardClient->expects($this->never())->method('awardDebriefDraft');
        $this->app->instance(ComparisonAwardAiClientInterface::class, $comparisonAwardClient);

        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        [, , , $approval] = $this->seedPendingApproval($user);

        $summaryResponse = $this->getJson(
            '/api/v1/approvals/' . $approval->id . '/summary',
            $this->authHeaders($tenantId, (string) $user->id),
        );
        $summaryResponse->assertStatus(503);
        $summaryResponse->assertJsonPath('data.ai_summary.feature_key', 'approval_ai_summary');
        $summaryResponse->assertJsonPath('data.ai_summary.available', false);
        $summaryResponse->assertJsonPath('data.ai_summary.provenance.source', 'deterministic');

        $response = $this->postJson(
            '/api/v1/approvals/' . $approval->id . '/approve',
            ['reason' => 'Proceed'],
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('data.status', 'approved');
        $this->assertSame('approved', $approval->fresh()?->status);
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
