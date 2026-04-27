<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Nexus\IntelligenceOperations\DTOs\AiCapabilityStatus;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Nexus\IntelligenceOperations\DTOs\AiStatusSnapshot;
use Nexus\ProcurementOperations\Contracts\VendorRecommendationLlmInterface;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationRequest;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationScoredCandidate;
use App\Adapters\Ai\Contracts\AiRuntimeStatusInterface;
use App\Models\DecisionTrailEntry;
use App\Models\Rfq;
use App\Models\RfqRecommendationArtifact;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use Tests\Feature\Api\ApiTestCase;

final class RfqRecommendationDecisionTrailTest extends ApiTestCase
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->bindAiRuntimeStatus();
    }

    public function testItRecordsVendorRecommendationDecisionTrailMetadata(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $rfq = $this->createRfq($user);
        $approved = $this->createVendor($tenantId, [
            'display_name' => 'Facility Experts',
            'status' => 'approved',
        ]);

        $this->bindVendorRecommendationLlm([
            'eligible_candidates' => [
                [
                    'vendor_id' => (string) $approved->id,
                    'vendor_name' => 'Facility Experts',
                    'fit_score' => 93,
                    'confidence_band' => 'high',
                    'provider_explanation' => 'Provider ranked Facility Experts first.',
                    'llm_insights' => ['Strong emergency maintenance fit.'],
                ],
            ],
            'provider_explanation' => 'Provider ranked Facility Experts first.',
            'provenance' => [
                'provider_name' => 'openrouter',
                'endpoint_group' => 'sourcing_recommendation',
                'model_revision' => 'openai/gpt-4.1-mini:2026-04-24',
                'prompt_template_version' => 'vendor-ranking@2026-04-24',
                'latency_ms' => 410,
                'confidence' => 0.93,
                'processed_at' => '2026-04-24T09:30:00+08:00',
            ],
        ]);

        $response = $this->postJson(
            '/api/v1/rfqs/' . $rfq->id . '/vendor-recommendations',
            ['categories' => ['facilities']],
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $response->assertOk();

        $artifact = RfqRecommendationArtifact::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $rfq->id)
            ->where('feature_key', 'vendor_ai_ranking')
            ->first();

        self::assertNotNull($artifact);
        self::assertSame('available', $artifact->status);

        $entry = DecisionTrailEntry::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $rfq->id)
            ->where('event_type', 'vendor_recommendation_generated')
            ->first();

        self::assertNotNull($entry);
        self::assertSame('vendor_recommendation', $entry->summary_payload['artifact_kind']);

        $trail = $this->getJson(
            '/api/v1/decision-trail/' . $entry->id,
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $trail->assertOk();
        $trail->assertJsonPath('data.event_type', 'vendor_recommendation_generated');
        $trail->assertJsonPath('data.metadata.artifact_kind', 'vendor_recommendation');
        $trail->assertJsonPath('data.metadata.description', 'Vendor recommendation generated');
    }

    public function testItRecordsBuyerShortlistDecisionTrailMetadata(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $rfq = $this->createRfq($user);
        $vendor1 = $this->createVendor($tenantId, ['status' => 'approved']);
        $vendor2 = $this->createVendor($tenantId, ['status' => 'approved']);

        $response = $this->putJson(
            '/api/v1/rfqs/' . $rfq->id . '/selected-vendors',
            ['vendor_ids' => [(string) $vendor1->id, (string) $vendor2->id]],
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $response->assertOk();
        self::assertSame(2, $this->selectedVendorCount($tenantId, (string) $rfq->id));

        $entry = DecisionTrailEntry::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $rfq->id)
            ->where('event_type', 'buyer_shortlist_replaced')
            ->first();

        self::assertNotNull($entry);
        self::assertSame(2, $entry->summary_payload['selection_count']);
        self::assertSame([(string) $vendor1->id, (string) $vendor2->id], $entry->summary_payload['selected_vendor_ids']);

        $trail = $this->getJson(
            '/api/v1/decision-trail/' . $entry->id,
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $trail->assertOk();
        $trail->assertJsonPath('data.event_type', 'buyer_shortlist_replaced');
        $trail->assertJsonPath('data.metadata.artifact_kind', 'buyer_shortlist');
        $trail->assertJsonPath('data.metadata.description', 'Buyer shortlist replaced with 2 vendors');
    }

    private function bindAiRuntimeStatus(): void
    {
        $snapshot = new AiStatusSnapshot(
            mode: AiStatusSchema::MODE_PROVIDER,
            globalHealth: AiStatusSchema::HEALTH_HEALTHY,
            capabilityDefinitions: [],
            capabilityStatuses: [
                'vendor_ai_ranking' => new AiCapabilityStatus(
                    featureKey: 'vendor_ai_ranking',
                    capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_SOURCING_RECOMMENDATION_INTELLIGENCE,
                    endpointGroup: AiStatusSchema::ENDPOINT_GROUP_SOURCING_RECOMMENDATION,
                    fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_UNAVAILABLE_MESSAGE,
                    messageKey: 'ai.vendor_ai_ranking.unavailable',
                    status: AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
                    available: true,
                    reasonCodes: [],
                    operatorCritical: true,
                    diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
                ),
            ],
            endpointGroupHealthSnapshots: [],
            reasonCodes: [],
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
     * @param array<string, mixed> $response
     */
    private function bindVendorRecommendationLlm(array $response): void
    {
        $this->app->instance(VendorRecommendationLlmInterface::class, new readonly class($response) implements VendorRecommendationLlmInterface {
            /**
             * @param array<string, mixed> $response
             */
            public function __construct(private array $response)
            {
            }

            /**
             * @param list<VendorRecommendationScoredCandidate> $candidates
             * @return array<string, mixed>
             */
            public function enrich(VendorRecommendationRequest $request, array $candidates): array
            {
                return $this->response;
            }
        });
    }

    private function createUser(string $tenantId): User
    {
        $this->createTenant($tenantId);

        /** @var User $user */
        $user = User::query()->create([
            'tenant_id' => $tenantId,
            'email' => 'trail-' . Str::lower($tenantId) . '@example.com',
            'name' => 'Trail User',
            'password_hash' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => '2026-04-22 00:00:00',
        ]);

        return $user;
    }

    private function createTenant(string $tenantId): void
    {
        if (Tenant::query()->where('id', $tenantId)->exists()) {
            return;
        }

        $tenant = new Tenant();
        $tenant->id = $tenantId;
        $tenant->code = 'T' . strtolower($tenantId);
        $tenant->name = 'Tenant ' . $tenantId;
        $tenant->email = strtolower($tenantId) . '@tenant.example.com';
        $tenant->status = 'active';
        $tenant->save();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createRfq(User $user, array $overrides = []): Rfq
    {
        /** @var Rfq $rfq */
        $rfq = Rfq::query()->create(array_merge([
            'tenant_id' => (string) $user->tenant_id,
            'rfq_number' => 'RFQ-2026-TRAIL-0001',
            'title' => 'Recommendation Trail RFQ',
            'description' => 'Facilities support',
            'category' => 'facilities',
            'status' => 'draft',
            'owner_id' => $user->id,
            'estimated_value' => 10000,
            'submission_deadline' => '2026-05-02 00:00:00',
        ], $overrides));

        return $rfq;
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createVendor(string $tenantId, array $overrides = []): Vendor
    {
        $unique = strtolower((string) Str::ulid());

        /** @var Vendor $vendor */
        $vendor = Vendor::query()->create(array_merge([
            'tenant_id' => $tenantId,
            'name' => 'Acme Holdings Sdn Bhd',
            'trading_name' => 'Acme Trading',
            'registration_number' => '201901234567',
            'country_code' => 'MY',
            'email' => 'acme-' . $unique . '@example.com',
            'phone' => '+60123456789',
            'status' => 'approved',
            'legal_name' => 'Acme Holdings Sdn Bhd',
            'display_name' => 'Acme Trading',
            'country_of_registration' => 'MY',
            'primary_contact_name' => 'Amina Zain',
            'primary_contact_email' => 'acme-' . $unique . '@example.com',
            'primary_contact_phone' => '+60123456789',
            'metadata' => [],
        ], $overrides));

        return $vendor;
    }

    private function selectedVendorCount(string $tenantId, string $rfqId): int
    {
        return DB::table('requisition_selected_vendors')
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $rfqId)
            ->count();
    }
}
