<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Adapters\Ai\Contracts\ComparisonAwardAiClientInterface;
use App\Adapters\Ai\Contracts\AiRuntimeStatusInterface;
use App\Adapters\Ai\DTOs\AwardDebriefDraftRequest;
use App\Adapters\Ai\DTOs\AwardDebriefDraftResponse;
use App\Adapters\Ai\DTOs\AwardGuidanceRequest;
use App\Adapters\Ai\DTOs\AwardGuidanceResponse;
use App\Models\Award;
use App\Models\ComparisonRun;
use App\Models\DecisionTrailEntry;
use App\Models\QuoteSubmission;
use App\Models\Rfq;
use App\Models\RfqLineItem;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Nexus\IntelligenceOperations\DTOs\AiCapabilityStatus;
use Nexus\IntelligenceOperations\DTOs\AiStatusSnapshot;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Tests\Feature\Api\ApiTestCase;

final class AwardWorkflowTest extends ApiTestCase
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
        /** @var User $user */
        $user = User::query()->create([
            'tenant_id' => (string) Str::ulid(),
            'email' => 'award-' . Str::lower((string) Str::ulid()) . '@example.com',
            'name' => 'Award User',
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
     * @return array{0: User, 1: Rfq, 2: ComparisonRun, 3: Award, 4: QuoteSubmission}
     */
    private function seedAward(User $user): array
    {
        [$user, $rfq, $run, $quote] = $this->seedAwardPrerequisites($user);

        /** @var Award $award */
        $award = Award::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'comparison_run_id' => $run->id,
            'vendor_id' => $quote->vendor_id,
            'status' => 'pending',
            'amount' => '1000.00',
            'currency' => 'USD',
            'split_details' => [],
            'protest_id' => null,
            'signoff_at' => null,
            'signed_off_by' => null,
        ]);

        return [$user, $rfq, $run, $award, $quote];
    }

    /**
     * @return array{0: User, 1: Rfq, 2: ComparisonRun, 3: QuoteSubmission}
     */
    private function seedAwardPrerequisites(User $user): array
    {
        $winnerVendorId = (string) Str::ulid();

        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-AWARD-' . Str::lower((string) Str::ulid()),
            'title' => 'Award RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'closed',
        ]);

        RfqLineItem::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'description' => 'Line',
            'quantity' => 1,
            'uom' => 'ea',
            'unit_price' => 10,
            'currency' => 'USD',
            'sort_order' => 0,
        ]);

        /** @var QuoteSubmission $quote */
        $quote = QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_id' => $winnerVendorId,
            'vendor_name' => 'Winner Vendor',
            'status' => 'ready',
            'submitted_at' => now(),
            'confidence' => 95.0,
            'line_items_count' => 1,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        $run = ComparisonRun::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'name' => 'Final comparison',
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
                    'normalized_lines' => [
                        ['source_line_id' => 'x'],
                    ],
                    'vendors' => [
                        [
                            'vendor_id' => $winnerVendorId,
                            'vendor_name' => 'Winner Vendor',
                            'quote_submission_id' => $quote->id,
                        ],
                        [
                            'vendor_id' => (string) Str::ulid(),
                            'vendor_name' => 'Runner Up Vendor',
                            'quote_submission_id' => null,
                        ],
                    ],
                ],
            ],
            'readiness_payload' => [],
            'status' => 'frozen',
            'version' => 1,
            'expires_at' => null,
            'discarded_at' => null,
            'discarded_by' => null,
        ]);

        return [$user, $rfq, $run, $quote];
    }

    public function test_single_winner_award_workflow_records_decision_trail_and_allows_debrief_before_signoff(): void
    {
        $this->bindAiRuntimeStatus([
            'award_ai_guidance' => new AiCapabilityStatus(
                featureKey: 'award_ai_guidance',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_AWARD_INTELLIGENCE,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_COMPARISON_AWARD,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_UNAVAILABLE_MESSAGE,
                messageKey: 'ai.award_ai_guidance.unavailable',
                status: AiStatusSchema::CAPABILITY_STATUS_UNAVAILABLE,
                available: false,
                reasonCodes: ['provider_unavailable'],
                operatorCritical: true,
                diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
            ),
        ]);
        $comparisonAwardClient = $this->createMock(ComparisonAwardAiClientInterface::class);
        $comparisonAwardClient->expects($this->never())->method('awardGuidance');
        $comparisonAwardClient->expects($this->never())->method('awardDebriefDraft');
        $this->app->instance(ComparisonAwardAiClientInterface::class, $comparisonAwardClient);

        [$user, $rfq, $run, $quote] = $this->seedAwardPrerequisites($this->createUser());

        $createResponse = $this->postJson(
            '/api/v1/awards',
            [
                'rfq_id' => $rfq->id,
                'comparison_run_id' => $run->id,
                'vendor_id' => $quote->vendor_id,
                'amount' => '1000.00',
                'currency' => 'USD',
            ],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $createResponse->assertCreated();
        $awardId = (string) $createResponse->json('data.id');

        $guidanceResponse = $this->getJson(
            '/api/v1/awards/' . $awardId . '/guidance',
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );
        $guidanceResponse->assertStatus(503);
        $guidanceResponse->assertJsonPath('data.ai_guidance.feature_key', 'award_ai_guidance');
        $guidanceResponse->assertJsonPath('data.ai_guidance.available', false);
        $guidanceResponse->assertJsonPath('data.ai_guidance.provenance.source', 'deterministic');

        $loserVendorId = (string) Str::ulid();
        QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_id' => $loserVendorId,
            'vendor_name' => 'Runner Up Vendor',
            'status' => 'ready',
            'submitted_at' => now(),
            'confidence' => 90.0,
            'line_items_count' => 1,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        $debriefResponse = $this->postJson(
            '/api/v1/awards/' . $awardId . '/debrief/' . $loserVendorId,
            ['message' => 'Thanks for participating.'],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $debriefResponse->assertOk();
        $debriefResponse->assertJsonPath('data.status', 'pending');

        $signoffResponse = $this->postJson(
            '/api/v1/awards/' . $awardId . '/signoff',
            [],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $signoffResponse->assertOk();
        $signoffResponse->assertJsonPath('data.status', 'signed_off');

        $repeatSignoffResponse = $this->postJson(
            '/api/v1/awards/' . $awardId . '/signoff',
            [],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $repeatSignoffResponse->assertOk();
        $repeatSignoffResponse->assertJsonPath('data.status', 'signed_off');

        $this->assertDatabaseHas('decision_trail_entries', [
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'comparison_run_id' => $run->id,
            'event_type' => 'award_created',
        ]);
        $this->assertDatabaseHas('decision_trail_entries', [
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'comparison_run_id' => $run->id,
            'event_type' => 'award_debriefed',
        ]);
        $this->assertDatabaseHas('decision_trail_entries', [
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'comparison_run_id' => $run->id,
            'event_type' => 'award_signed_off',
        ]);

        $events = DecisionTrailEntry::query()
            ->where('tenant_id', $run->tenant_id)
            ->where('rfq_id', $rfq->id)
            ->where('comparison_run_id', $run->id)
            ->orderBy('sequence')
            ->pluck('event_type')
            ->all();

        $this->assertSame(
            ['award_created', 'award_debriefed', 'award_signed_off'],
            $events,
        );
    }

    public function test_guidance_returns_provider_payload_for_award(): void
    {
        $this->bindAiRuntimeStatus([
            'award_ai_guidance' => new AiCapabilityStatus(
                featureKey: 'award_ai_guidance',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_AWARD_INTELLIGENCE,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_COMPARISON_AWARD,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                messageKey: 'ai.award_ai_guidance.available',
                status: AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
                available: true,
                reasonCodes: [],
                operatorCritical: true,
                diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
            ),
        ]);
        $comparisonAwardClient = $this->createMock(ComparisonAwardAiClientInterface::class);
        $comparisonAwardClient->expects($this->once())
            ->method('awardGuidance')
            ->with($this->callback(static function (AwardGuidanceRequest $request): bool {
                return $request->tenantId !== ''
                    && $request->awardId !== ''
                    && $request->comparisonRunId !== ''
                    && isset($request->comparisonContext['snapshot'], $request->comparisonContext['matrix']);
            }))
            ->willReturn(new AwardGuidanceResponse([
                'headline' => 'Proceed with the lowest compliant vendor.',
                'risk_flags' => ['single_source_dependency'],
            ]));
        $comparisonAwardClient->expects($this->never())->method('comparisonOverlay');
        $comparisonAwardClient->expects($this->never())->method('awardDebriefDraft');
        $comparisonAwardClient->expects($this->never())->method('approvalSummary');
        $this->app->instance(ComparisonAwardAiClientInterface::class, $comparisonAwardClient);

        [$user, $rfq, $run, $award] = $this->seedAward($this->createUser());

        $response = $this->getJson(
            '/api/v1/awards/' . $award->id . '/guidance',
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('data.ai_guidance.feature_key', 'award_ai_guidance');
        $response->assertJsonPath('data.ai_guidance.available', true);
        $response->assertJsonPath('data.ai_guidance.payload.headline', 'Proceed with the lowest compliant vendor.');
        $response->assertJsonPath('data.ai_guidance.provenance.source', 'provider');

        $run = $run->fresh();
        $storedGuidance = $run?->response_payload['ai_artifacts']['award_guidance'][$award->id] ?? null;
        $this->assertIsArray($storedGuidance);
        $this->assertSame('award_ai_guidance', $storedGuidance['feature_key'] ?? null);
        $this->assertSame(true, $storedGuidance['available'] ?? null);

        $entry = DecisionTrailEntry::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('comparison_run_id', $run->id)
            ->where('event_type', 'award_ai_guidance_generated:' . $award->id)
            ->first();
        $this->assertNotNull($entry);

        $trailResponse = $this->getJson(
            '/api/v1/decision-trail/' . $entry->id,
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );
        $trailResponse->assertOk();
        $trailResponse->assertJsonPath('data.metadata.artifact_origin', 'provider_drafted');
        $trailResponse->assertJsonPath('data.metadata.artifact_kind', 'award_ai_guidance');
        $trailResponse->assertJsonPath('data.metadata.artifact_subject_id', $award->id);
        $trailResponse->assertJsonPath('data.metadata.provenance.source', 'provider');
    }

    public function test_guidance_returns_persisted_artifact_when_runtime_is_unavailable(): void
    {
        [$user, , $run, $award] = $this->seedAward($this->createUser());

        $artifact = [
            'feature_key' => 'award_ai_guidance',
            'capability_group' => AiStatusSchema::CAPABILITY_GROUP_AWARD_INTELLIGENCE,
            'available' => true,
            'status' => AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
            'manual_continuity' => 'available',
            'payload' => ['headline' => 'Persisted guidance'],
            'provenance' => [
                'source' => 'provider',
                'provider_name' => 'openrouter',
                'endpoint_group' => AiStatusSchema::ENDPOINT_GROUP_COMPARISON_AWARD,
            ],
        ];

        $run->response_payload = array_replace_recursive((array) $run->response_payload, [
            'ai_artifacts' => [
                'award_guidance' => [
                    (string) $award->id => $artifact,
                ],
            ],
        ]);
        $run->save();

        $this->bindAiRuntimeStatus([
            'award_ai_guidance' => new AiCapabilityStatus(
                featureKey: 'award_ai_guidance',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_AWARD_INTELLIGENCE,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_COMPARISON_AWARD,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                messageKey: 'ai.award_ai_guidance.unavailable',
                status: AiStatusSchema::CAPABILITY_STATUS_UNAVAILABLE,
                available: false,
                reasonCodes: ['provider_unavailable'],
                operatorCritical: true,
                diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
            ),
        ]);

        $comparisonAwardClient = $this->createMock(ComparisonAwardAiClientInterface::class);
        $comparisonAwardClient->expects($this->never())->method('awardGuidance');
        $comparisonAwardClient->expects($this->never())->method('awardDebriefDraft');
        $comparisonAwardClient->expects($this->never())->method('comparisonOverlay');
        $comparisonAwardClient->expects($this->never())->method('approvalSummary');
        $this->app->instance(ComparisonAwardAiClientInterface::class, $comparisonAwardClient);

        $response = $this->getJson(
            '/api/v1/awards/' . $award->id . '/guidance',
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('data.ai_guidance.available', true);
        $response->assertJsonPath('data.ai_guidance.payload.headline', 'Persisted guidance');
    }

    public function test_debrief_draft_returns_provider_payload_for_non_winning_vendor(): void
    {
        $this->bindAiRuntimeStatus([
            'award_ai_guidance' => new AiCapabilityStatus(
                featureKey: 'award_ai_guidance',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_AWARD_INTELLIGENCE,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_COMPARISON_AWARD,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                messageKey: 'ai.award_ai_guidance.available',
                status: AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
                available: true,
                reasonCodes: [],
                operatorCritical: true,
                diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
            ),
        ]);

        [$user, $rfq, $run, $award] = $this->seedAward($this->createUser());
        $loserVendorId = (string) Str::ulid();

        QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_id' => $loserVendorId,
            'vendor_name' => 'Runner Up Vendor',
            'status' => 'ready',
            'submitted_at' => now(),
            'confidence' => 88.0,
            'line_items_count' => 1,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        $comparisonAwardClient = $this->createMock(ComparisonAwardAiClientInterface::class);
        $comparisonAwardClient->expects($this->never())->method('comparisonOverlay');
        $comparisonAwardClient->expects($this->never())->method('awardGuidance');
        $comparisonAwardClient->expects($this->never())->method('approvalSummary');
        $comparisonAwardClient->expects($this->once())
            ->method('awardDebriefDraft')
            ->with($this->callback(static function (AwardDebriefDraftRequest $request) use ($award, $loserVendorId): bool {
                return $request->awardId === $award->id
                    && $request->vendorId === $loserVendorId
                    && isset($request->comparisonContext['snapshot']);
            }))
            ->willReturn(new AwardDebriefDraftResponse([
                'draft_message' => 'Thank you for your proposal. The award went to a lower-risk commercial bid.',
                'talking_points' => ['pricing variance', 'delivery confidence'],
            ]));
        $this->app->instance(ComparisonAwardAiClientInterface::class, $comparisonAwardClient);

        $response = $this->getJson(
            '/api/v1/awards/' . $award->id . '/debrief-draft/' . $loserVendorId,
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('data.ai_debrief_draft.feature_key', 'award_ai_guidance');
        $response->assertJsonPath('data.ai_debrief_draft.available', true);
        $response->assertJsonPath('data.ai_debrief_draft.payload.draft_message', 'Thank you for your proposal. The award went to a lower-risk commercial bid.');
        $response->assertJsonPath('data.ai_debrief_draft.provenance.source', 'provider');

        $run = $run->fresh();
        $storedDraft = $run?->response_payload['ai_artifacts']['award_debrief_draft'][$award->id][$loserVendorId] ?? null;
        $this->assertIsArray($storedDraft);
        $this->assertSame('award_ai_guidance', $storedDraft['feature_key'] ?? null);

        $entry = DecisionTrailEntry::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('comparison_run_id', $run->id)
            ->where('event_type', 'award_ai_debrief_draft_generated')
            ->first();
        $this->assertNotNull($entry);

        $trailResponse = $this->getJson(
            '/api/v1/decision-trail/' . $entry->id,
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );
        $trailResponse->assertOk();
        $trailResponse->assertJsonPath('data.metadata.artifact_kind', 'award_ai_debrief_draft');
        $trailResponse->assertJsonPath('data.metadata.artifact_subject_id', $loserVendorId);
        $trailResponse->assertJsonPath('data.metadata.award_id', $award->id);
        $trailResponse->assertJsonPath('data.metadata.provenance.source', 'provider');
    }

    public function test_store_rejects_non_final_comparison_run_for_award_creation(): void
    {
        [$user, $rfq, $run, $quote] = $this->seedAwardPrerequisites($this->createUser());
        $run->status = 'draft';
        $run->save();

        $response = $this->postJson(
            '/api/v1/awards',
            [
                'rfq_id' => $rfq->id,
                'comparison_run_id' => $run->id,
                'vendor_id' => $quote->vendor_id,
                'amount' => '1000.00',
                'currency' => 'USD',
            ],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Comparison run is not finalized for award creation');
    }

    public function test_store_requires_comparison_run_for_alpha_award_creation(): void
    {
        [$user, $rfq, , $quote] = $this->seedAwardPrerequisites($this->createUser());

        $response = $this->postJson(
            '/api/v1/awards',
            [
                'rfq_id' => $rfq->id,
                'vendor_id' => $quote->vendor_id,
                'amount' => '1000.00',
                'currency' => 'USD',
            ],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Validation failed');
        $response->assertJsonPath('details.comparison_run_id.0', 'The comparison run id field is required.');
    }

    public function test_award_actions_return_not_found_for_other_tenant(): void
    {
        [$user, $rfq, , $award] = $this->seedAward($this->createUser());
        $otherUser = $this->createUser();

        $createResponse = $this->postJson(
            '/api/v1/awards',
            [
                'rfq_id' => $rfq->id,
                'comparison_run_id' => $award->comparison_run_id,
                'vendor_id' => $award->vendor_id,
                'amount' => '1000.00',
                'currency' => 'USD',
            ],
            $this->authHeaders((string) $otherUser->tenant_id, (string) $otherUser->id),
        );
        $createResponse->assertNotFound();

        $indexResponse = $this->getJson(
            '/api/v1/awards?rfqId=' . $rfq->id,
            $this->authHeaders((string) $otherUser->tenant_id, (string) $otherUser->id),
        );
        $indexResponse->assertOk();
        $indexResponse->assertJsonCount(0, 'data');

        $guidanceResponse = $this->getJson(
            '/api/v1/awards/' . $award->id . '/guidance',
            $this->authHeaders((string) $otherUser->tenant_id, (string) $otherUser->id),
        );
        $guidanceResponse->assertNotFound();

        $signoffResponse = $this->postJson(
            '/api/v1/awards/' . $award->id . '/signoff',
            [],
            $this->authHeaders((string) $otherUser->tenant_id, (string) $otherUser->id),
        );
        $signoffResponse->assertNotFound();

        $debriefResponse = $this->postJson(
            '/api/v1/awards/' . $award->id . '/debrief/' . (string) Str::ulid(),
            ['message' => 'Hidden'],
            $this->authHeaders((string) $otherUser->tenant_id, (string) $otherUser->id),
        );
        $debriefResponse->assertNotFound();
    }

    public function test_index_returns_live_award_rows_for_rfq(): void
    {
        [$user, $rfq, , $award] = $this->seedAward($this->createUser());

        $response = $this->getJson(
            '/api/v1/awards?rfqId=' . $rfq->id,
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $award->id);
        $response->assertJsonPath('data.0.rfq_id', $rfq->id);
        $response->assertJsonPath('data.0.vendor_name', 'Winner Vendor');
        $response->assertJsonPath('data.0.comparison.vendors.0.vendor_id', $award->vendor_id);
    }

    public function test_store_creates_award_for_vendor_submitted_to_rfq(): void
    {
        [$user, $rfq, $run, $quote] = $this->seedAwardPrerequisites($this->createUser());

        $response = $this->postJson(
            '/api/v1/awards',
            [
                'rfq_id' => $rfq->id,
                'comparison_run_id' => $run->id,
                'vendor_id' => $quote->vendor_id,
                'amount' => '1000.00',
                'currency' => 'usd',
                'split_details' => [],
            ],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertCreated();
        $response->assertJsonPath('data.vendor_name', 'Winner Vendor');
        $this->assertDatabaseHas('awards', [
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_id' => $quote->vendor_id,
        ]);
    }

    public function test_store_rejects_vendor_not_linked_to_rfq(): void
    {
        [$user, $rfq, $run] = $this->seedAwardPrerequisites($this->createUser());

        $response = $this->postJson(
            '/api/v1/awards',
            [
                'rfq_id' => $rfq->id,
                'comparison_run_id' => $run->id,
                'vendor_id' => (string) Str::ulid(),
                'amount' => '1000.00',
                'currency' => 'USD',
            ],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertNotFound();
    }

    public function test_signoff_updates_award_status_and_timestamps(): void
    {
        [$user, , , $award] = $this->seedAward($this->createUser());

        try {
            Carbon::setTestNow(Carbon::parse('2026-03-30 12:00:00', 'UTC'));

            $response = $this->postJson(
                '/api/v1/awards/' . $award->id . '/signoff',
                [],
                $this->authHeaders((string) $user->tenant_id, (string) $user->id),
            );

            $response->assertOk();
            $response->assertJsonPath('data.status', 'signed_off');

            $award->refresh();
            self::assertSame('signed_off', $award->status);
            self::assertNotNull($award->signoff_at);
            self::assertSame($user->id, $award->signed_off_by);

            Carbon::setTestNow(Carbon::parse('2026-03-30 12:05:00', 'UTC'));
            $secondResponse = $this->postJson(
                '/api/v1/awards/' . $award->id . '/signoff',
                [],
                $this->authHeaders((string) $user->tenant_id, (string) $user->id),
            );

            $secondResponse->assertOk();
            $award->refresh();
            self::assertSame('signed_off', $award->status);
            self::assertSame('2026-03-30T12:00:00+00:00', $award->signoff_at?->toAtomString());
            self::assertSame($user->id, $award->signed_off_by);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_debrief_returns_live_response_for_non_winning_vendor(): void
    {
        [$user, $rfq, $run, $award] = $this->seedAward($this->createUser());
        $loserVendorId = (string) Str::ulid();

        QuoteSubmission::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'vendor_id' => $loserVendorId,
            'vendor_name' => 'Loser Vendor',
            'status' => 'ready',
            'submitted_at' => now(),
            'confidence' => 90.0,
            'line_items_count' => 1,
            'warnings_count' => 0,
            'errors_count' => 0,
        ]);

        $response = $this->postJson(
            '/api/v1/awards/' . $award->id . '/debrief/' . $loserVendorId,
            ['message' => 'Thanks for participating.'],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('data.award_id', $award->id);
        $response->assertJsonPath('data.vendor_id', $loserVendorId);
        $response->assertJsonPath('data.vendor_name', 'Loser Vendor');
        $response->assertJsonPath('data.message', 'Thanks for participating.');
        $this->assertDatabaseHas('debriefs', [
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'award_id' => $award->id,
            'vendor_id' => $loserVendorId,
            'message' => 'Thanks for participating.',
        ]);

        $secondResponse = $this->postJson(
            '/api/v1/awards/' . $award->id . '/debrief/' . $loserVendorId,
            ['message' => 'Thanks for participating.'],
            $this->authHeaders((string) $user->tenant_id, (string) $user->id),
        );

        $secondResponse->assertOk();
        $this->assertSame($response->json('data.debriefed_at'), $secondResponse->json('data.debriefed_at'));
        $this->assertDatabaseCount('debriefs', 1);
        $this->assertDatabaseHas('decision_trail_entries', [
            'tenant_id' => $user->tenant_id,
            'comparison_run_id' => $run->id,
            'rfq_id' => $rfq->id,
            'event_type' => 'award_debriefed',
        ]);
    }
}
