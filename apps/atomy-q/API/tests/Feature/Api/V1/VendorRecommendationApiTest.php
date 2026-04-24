<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use Nexus\IntelligenceOperations\DTOs\AiCapabilityStatus;
use Nexus\IntelligenceOperations\DTOs\AiStatusSnapshot;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use App\Adapters\Ai\Contracts\AiRuntimeStatusInterface;
use App\Models\Rfq;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use Nexus\ProcurementOperations\Contracts\VendorRecommendationLlmInterface;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationRequest;
use Nexus\ProcurementOperations\DTOs\VendorRecommendation\VendorRecommendationScoredCandidate;
use Tests\Feature\Api\ApiTestCase;

final class VendorRecommendationApiTest extends ApiTestCase
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

    public function testItReturnsApprovedVendorRecommendationsWithReasonsAndWarnings(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $rfq = $this->createRfq($user, [
            'category' => 'facilities',
            'description' => 'Emergency maintenance services for Malaysia offices.',
        ]);
        $approved = $this->createVendor($tenantId, [
            'display_name' => 'Facility Experts',
            'status' => 'approved',
            'metadata' => [
                'categories' => ['facilities'],
                'capabilities' => ['emergency-maintenance'],
                'regions' => ['MY'],
                'last_active_at' => '2026-04-12T00:00:00Z',
            ],
        ]);
        $draft = $this->createVendor($tenantId, [
            'display_name' => 'Draft Facility Vendor',
            'status' => 'draft',
            'metadata' => [
                'categories' => ['facilities'],
                'regions' => ['MY'],
            ],
        ]);
        $this->bindVendorRecommendationLlm([
            'eligible_candidates' => [
                [
                    'vendor_id' => (string) $approved->id,
                    'vendor_name' => 'Facility Experts',
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
                'request_trace_id' => 'trace-vendor-1',
                'input_hash' => 'sha256:input',
                'output_hash' => 'sha256:output',
                'latency_ms' => 412,
                'confidence' => 0.93,
                'reliability_hints' => ['provider_confidence' => 'high'],
                'processed_at' => '2026-04-24T09:30:00+08:00',
            ],
        ]);

        $response = $this->postJson(
            '/api/v1/rfqs/' . $rfq->id . '/vendor-recommendations',
            [
                'categories' => ['facilities'],
                'description' => 'Need emergency maintenance response.',
                'geography' => 'MY',
                'spend_band' => 'medium',
                'line_item_summary' => ['emergency maintenance'],
            ],
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('data.status', 'available');
        $response->assertJsonPath('data.rfq_id', (string) $rfq->id);
        $response->assertJsonPath('data.eligible_candidates.0.vendor_id', (string) $approved->id);
        $response->assertJsonPath('data.eligible_candidates.0.vendor_name', 'Facility Experts');
        $response->assertJsonPath('data.eligible_candidates.0.provider_explanation', 'Provider ranked Facility Experts first.');
        $response->assertJsonPath('data.provenance.provider_name', 'openrouter');
        $response->assertJsonPath('data.candidates.0.vendor_id', (string) $approved->id);
        $this->assertIsInt($response->json('data.eligible_candidates.0.fit_score'));
        $this->assertNotEmpty($response->json('data.eligible_candidates.0.deterministic_reasons'));
        $this->assertIsArray($response->json('data.eligible_candidates.0.warning_flags'));
        $this->assertIsArray($response->json('data.eligible_candidates.0.warnings'));
        $this->assertContains((string) $draft->id, array_column($response->json('data.excluded_candidates'), 'vendor_id'));
        $this->assertNotContains((string) $draft->id, array_column($response->json('data.eligible_candidates'), 'vendor_id'));
    }

    public function testItReturns422ForMalformedRecommendationContext(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $rfq = $this->createRfq($user);

        $response = $this->postJson(
            '/api/v1/rfqs/' . $rfq->id . '/vendor-recommendations',
            [
                'categories' => [''],
                'line_item_summary' => ['line'],
            ],
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $response->assertUnprocessable();
    }

    public function testItReturns404ForCrossTenantRfqAccess(): void
    {
        $tenantA = (string) Str::ulid();
        $tenantB = (string) Str::ulid();
        $userA = $this->createUser($tenantA);
        $userB = $this->createUser($tenantB);
        $rfq = $this->createRfq($userA);

        $this->postJson(
            '/api/v1/rfqs/' . $rfq->id . '/vendor-recommendations',
            [
                'categories' => ['facilities'],
                'description' => 'Need approved vendors.',
            ],
            $this->authHeaders($tenantB, (string) $userB->id),
        )->assertNotFound();
    }

    public function testItReturnsEmptyCandidateSetWhenOnlyNonApprovedVendorsExist(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $rfq = $this->createRfq($user);
        $this->createVendor($tenantId, ['status' => 'suspended']);
        $this->bindVendorRecommendationLlm([
            'eligible_candidates' => [],
            'provider_explanation' => 'No approved vendors met the provider threshold.',
            'provenance' => [
                'provider_name' => 'openrouter',
                'endpoint_group' => 'sourcing_recommendation',
                'model_revision' => 'openai/gpt-4.1-mini:2026-04-24',
                'prompt_template_version' => 'vendor-ranking@2026-04-24',
                'request_trace_id' => 'trace-vendor-2',
                'input_hash' => 'sha256:input-2',
                'output_hash' => 'sha256:output-2',
                'latency_ms' => 301,
                'confidence' => 0.42,
                'reliability_hints' => ['provider_confidence' => 'low'],
                'processed_at' => '2026-04-24T10:15:00+08:00',
            ],
        ]);

        $response = $this->postJson(
            '/api/v1/rfqs/' . $rfq->id . '/vendor-recommendations',
            [
                'categories' => ['facilities'],
                'description' => 'Need approved vendors.',
            ],
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $response->assertOk();
        $response->assertJsonPath('data.status', 'available');
        $response->assertJsonPath('data.eligible_candidates', []);
        $response->assertJsonPath('data.candidates', []);
        $response->assertJsonPath('data.provider_explanation', 'No approved vendors met the provider threshold.');
        $response->assertJsonPath('data.provenance.provider_name', 'openrouter');
        $this->assertSame('suspended', $response->json('data.excluded_candidates.0.status'));
    }

    private function createUser(string $tenantId): User
    {
        $this->createTenant($tenantId);

        /** @var User $user */
        $user = User::query()->create([
            'tenant_id' => $tenantId,
            'email' => 'recommend-' . Str::lower($tenantId) . '@example.com',
            'name' => 'Recommendation User',
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
     * @param array<string, mixed> $overrides
     */
    private function createRfq(User $user, array $overrides = []): Rfq
    {
        /** @var Rfq $rfq */
        $rfq = Rfq::query()->create(array_merge([
            'tenant_id' => (string) $user->tenant_id,
            'rfq_number' => 'RFQ-2026-REC-0001',
            'title' => 'Recommendation RFQ',
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
}
