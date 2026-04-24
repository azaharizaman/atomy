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
use Tests\Feature\Api\ApiTestCase;

final class VendorRecommendationAiGateTest extends ApiTestCase
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

    public function testItReturnsStructuredUnavailableForVendorAiRankingWhenCapabilityIsUnavailable(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $rfq = $this->createRfq($user);
        $this->bindAiRuntimeStatus([
            'vendor_ai_ranking' => new AiCapabilityStatus(
                featureKey: 'vendor_ai_ranking',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_SOURCING_RECOMMENDATION_INTELLIGENCE,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_SOURCING_RECOMMENDATION,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_UNAVAILABLE_MESSAGE,
                messageKey: 'ai.vendor_ai_ranking.unavailable',
                status: AiStatusSchema::CAPABILITY_STATUS_UNAVAILABLE,
                available: false,
                reasonCodes: ['provider_unavailable'],
                operatorCritical: true,
                diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
            ),
        ]);

        $response = $this->postJson(
            '/api/v1/rfqs/' . $rfq->id . '/vendor-recommendations',
            ['categories' => ['facilities']],
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $response->assertStatus(503);
        $response->assertJsonPath('data.feature_key', 'vendor_ai_ranking');
        $response->assertJsonPath('data.status', AiStatusSchema::CAPABILITY_STATUS_UNAVAILABLE);
        $response->assertJsonPath('data.available', false);
        $response->assertJsonPath('data.reason_codes.0', 'provider_unavailable');
    }

    public function testItReturnsStructuredUnavailableForRecommendationEndpointsWhenFeatureIsNotDefined(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $this->bindAiRuntimeStatus([]);

        $response = $this->getJson(
            '/api/v1/recommendations/run-123',
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $response->assertStatus(503);
        $response->assertJsonPath('data.feature_key', 'recommendation_ai_endpoint');
        $response->assertJsonPath('data.status', AiStatusSchema::CAPABILITY_STATUS_UNAVAILABLE);
        $response->assertJsonPath('data.available', false);
    }

    public function testItReturnsStructuredUnavailableWhenRuntimeStatusProbeThrows(): void
    {
        $tenantId = (string) Str::ulid();
        $user = $this->createUser($tenantId);
        $rfq = $this->createRfq($user);

        $this->app->instance(AiRuntimeStatusInterface::class, new readonly class implements AiRuntimeStatusInterface {
            public function snapshot(): AiStatusSnapshot
            {
                throw new \RuntimeException('runtime probe failed');
            }

            public function capabilityStatus(string $featureKey): ?AiCapabilityStatus
            {
                return null;
            }

            public function providerName(): string
            {
                return 'openrouter';
            }
        });

        $response = $this->postJson(
            '/api/v1/rfqs/' . $rfq->id . '/vendor-recommendations',
            ['categories' => ['facilities']],
            $this->authHeaders($tenantId, (string) $user->id),
        );

        $response->assertStatus(503);
        $response->assertJsonPath('data.feature_key', 'vendor_ai_ranking');
        $response->assertJsonPath('data.status', AiStatusSchema::CAPABILITY_STATUS_UNAVAILABLE);
        $response->assertJsonPath('data.reason_codes.0', 'provider_unavailable');
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

    private function createUser(string $tenantId): User
    {
        $this->createTenant($tenantId);

        /** @var User $user */
        $user = User::query()->create([
            'tenant_id' => $tenantId,
            'email' => 'recommend-gate-' . Str::lower($tenantId) . '@example.com',
            'name' => 'Recommendation Gate User',
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

    private function createRfq(User $user): Rfq
    {
        /** @var Rfq $rfq */
        $rfq = Rfq::query()->create([
            'tenant_id' => (string) $user->tenant_id,
            'rfq_number' => 'RFQ-2026-REC-GATE-0001',
            'title' => 'Recommendation RFQ',
            'description' => 'Facilities support',
            'category' => 'facilities',
            'status' => 'draft',
            'owner_id' => $user->id,
            'estimated_value' => 10000,
            'submission_deadline' => '2026-05-02 00:00:00',
        ]);

        return $rfq;
    }
}
