<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Adapters\Ai\Contracts\AiRuntimeStatusInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Nexus\IntelligenceOperations\DTOs\AiCapabilityStatus;
use Nexus\IntelligenceOperations\DTOs\AiStatusSnapshot;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Nexus\IntelligenceOperations\Contracts\AiStatusCoordinatorInterface;
use Tests\Feature\Api\ApiTestCase;

final class AiStatusApiTest extends ApiTestCase
{
    public function testItIsPublicAndReturnsStatusWithoutAuth(): void
    {
        $response = $this->getJson('/api/v1/ai/status');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'mode',
                'global_health',
                'reason_codes',
                'generated_at',
                'capability_definitions',
                'capability_statuses',
                'endpoint_groups',
                'provider_name',
            ],
        ]);
        $response->assertJsonPath('data.provider_name', 'openrouter');
    }

    public function testItReturnsDisabledStatusWhenAiModeIsOff(): void
    {
        config()->set('atomy.ai.mode', AiStatusSchema::MODE_OFF);
        config()->set('atomy.ai.provider.key', 'openrouter');
        config()->set('atomy.ai.endpoints.document.uri', 'https://openrouter.example.test/document');

        $response = $this->getJson('/api/v1/ai/status');

        $response->assertOk();
        $response->assertJsonPath('data.mode', AiStatusSchema::MODE_OFF);
        $response->assertJsonPath('data.global_health', AiStatusSchema::HEALTH_DISABLED);
        $response->assertJsonPath('data.provider_name', 'openrouter');
        $response->assertJsonPath(
            'data.capability_statuses.document_intelligence.status',
            AiStatusSchema::CAPABILITY_STATUS_DISABLED,
        );
        $response->assertJsonPath(
            'data.endpoint_groups.0.health',
            AiStatusSchema::HEALTH_DISABLED,
        );
        $this->assertContains('ai_disabled_by_config', $response->json('data.reason_codes'));
    }

    public function testItReturnsDeterministicFallbackStatuses(): void
    {
        config()->set('atomy.ai.mode', AiStatusSchema::MODE_DETERMINISTIC);

        $response = $this->getJson('/api/v1/ai/status');

        $response->assertOk();
        $response->assertJsonPath('data.mode', AiStatusSchema::MODE_DETERMINISTIC);
        $response->assertJsonPath('data.global_health', AiStatusSchema::HEALTH_DEGRADED);
        $response->assertJsonPath(
            'data.capability_statuses.document_intelligence.status',
            AiStatusSchema::CAPABILITY_STATUS_DEGRADED,
        );
        $response->assertJsonPath(
            'data.capability_statuses.normalization_intelligence.status',
            AiStatusSchema::CAPABILITY_STATUS_DEGRADED,
        );
        $capabilityDefinitions = collect($response->json('data.capability_definitions'))->keyBy('feature_key');
        self::assertTrue((bool) $capabilityDefinitions['normalization_intelligence']['has_manual_fallback']);
        self::assertSame(
            AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
            $capabilityDefinitions['normalization_intelligence']['fallback_ui_mode'],
        );
        $this->assertContains('deterministic_fallback_mode', $response->json('data.reason_codes'));
    }

    public function testItExposesQuoteIntakeAndNormalizationFeaturePolicies(): void
    {
        config()->set('atomy.ai.mode', AiStatusSchema::MODE_DETERMINISTIC);

        $response = $this->getJson('/api/v1/ai/status');

        $response->assertOk();

        $definitions = collect($response->json('data.capability_definitions'))->keyBy('feature_key');
        $statuses = collect($response->json('data.capability_statuses'));

        foreach ([
            'quote_document_extraction' => AiStatusSchema::CAPABILITY_GROUP_DOCUMENT_INTELLIGENCE,
            'quote_reparse_extraction' => AiStatusSchema::CAPABILITY_GROUP_DOCUMENT_INTELLIGENCE,
            'normalization_suggestions' => AiStatusSchema::CAPABILITY_GROUP_NORMALIZATION_INTELLIGENCE,
            'normalization_manual_mapping' => AiStatusSchema::CAPABILITY_GROUP_NORMALIZATION_INTELLIGENCE,
            'vendor_ai_ranking' => AiStatusSchema::CAPABILITY_GROUP_SOURCING_RECOMMENDATION_INTELLIGENCE,
            'vendor_manual_selection' => AiStatusSchema::CAPABILITY_GROUP_SOURCING_RECOMMENDATION_INTELLIGENCE,
        ] as $featureKey => $capabilityGroup) {
            self::assertArrayHasKey($featureKey, $definitions);
            self::assertArrayHasKey($featureKey, $statuses);
            self::assertSame($capabilityGroup, $definitions[$featureKey]['capability_group']);
            self::assertSame($capabilityGroup, $statuses[$featureKey]['capability_group']);
        }

        self::assertTrue((bool) $definitions['quote_document_extraction']['requires_ai']);
        self::assertTrue((bool) $definitions['quote_document_extraction']['has_manual_fallback']);
        self::assertSame(AiStatusSchema::ENDPOINT_GROUP_DOCUMENT, $definitions['quote_document_extraction']['endpoint_group']);
        self::assertSame(
            AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
            $definitions['quote_document_extraction']['fallback_ui_mode'],
        );

        self::assertFalse((bool) $definitions['normalization_manual_mapping']['requires_ai']);
        self::assertTrue((bool) $statuses['normalization_manual_mapping']['available']);
        self::assertSame(AiStatusSchema::CAPABILITY_STATUS_AVAILABLE, $statuses['normalization_manual_mapping']['status']);

        self::assertTrue((bool) $definitions['vendor_ai_ranking']['requires_ai']);
        self::assertFalse((bool) $definitions['vendor_ai_ranking']['has_manual_fallback']);
        self::assertSame(AiStatusSchema::CAPABILITY_STATUS_UNAVAILABLE, $statuses['vendor_ai_ranking']['status']);
        self::assertFalse((bool) $definitions['vendor_manual_selection']['requires_ai']);
        self::assertTrue((bool) $statuses['vendor_manual_selection']['available']);
        self::assertArrayNotHasKey('recommendation_ai_endpoint', $definitions);
        self::assertArrayNotHasKey('recommendation_ai_endpoint', $statuses);
    }

    public function testItReflectsMixedProviderEndpointHealth(): void
    {
        config()->set('atomy.ai.mode', AiStatusSchema::MODE_PROVIDER);
        config()->set('atomy.ai.provider.key', 'huggingface');
        config()->set('atomy.ai.provider.name', 'hf-public');
        config()->set('atomy.ai.provider.default_timeout_seconds', 5);
        config()->set('atomy.ai.provider.default_auth_token', 'hf-secret-token');
        config()->set('atomy.ai.endpoints.document.uri', 'https://hf.example.test/document');
        config()->set('atomy.ai.endpoints.normalization.uri', 'https://hf.example.test/normalization');
        config()->set('atomy.ai.endpoints.sourcing_recommendation.uri', 'https://hf.example.test/recommendation');
        config()->set('atomy.ai.endpoints.comparison_award.uri', 'https://hf.example.test/comparison-award');
        config()->set('atomy.ai.endpoints.insight.uri', 'https://hf.example.test/insight');
        config()->set('atomy.ai.endpoints.governance.uri', 'https://hf.example.test/governance');

        Http::fake([
            'https://hf.example.test/document/health' => Http::response(['ok' => true], 200),
            'https://hf.example.test/normalization/health' => static fn (): never => throw new ConnectionException('timeout'),
            'https://hf.example.test/recommendation/health' => Http::response(['ok' => true], 200),
            'https://hf.example.test/comparison-award/health' => Http::response(['ok' => true], 200),
            'https://hf.example.test/insight/health' => Http::response(['ok' => true], 200),
            'https://hf.example.test/governance/health' => Http::response(['error' => 'down'], 503),
            '*' => Http::response(['error' => 'wrong-route'], 405),
        ]);

        $response = $this->getJson('/api/v1/ai/status');

        $response->assertOk();
        $response->assertJsonPath('data.mode', AiStatusSchema::MODE_PROVIDER);
        $response->assertJsonPath('data.provider_name', 'hf-public');
        $response->assertJsonPath('data.global_health', AiStatusSchema::HEALTH_DEGRADED);
        $response->assertJsonPath(
            'data.capability_statuses.document_intelligence.status',
            AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
        );
        $response->assertJsonPath(
            'data.capability_statuses.normalization_intelligence.status',
            AiStatusSchema::CAPABILITY_STATUS_DEGRADED,
        );
        $response->assertJsonPath(
            'data.capability_statuses.governance_intelligence.status',
            AiStatusSchema::CAPABILITY_STATUS_DEGRADED,
        );

        $endpointGroups = collect($response->json('data.endpoint_groups'))->keyBy('endpoint_group');
        self::assertSame(AiStatusSchema::HEALTH_HEALTHY, $endpointGroups['document']['health']);
        self::assertSame(AiStatusSchema::HEALTH_UNAVAILABLE, $endpointGroups['normalization']['health']);
        self::assertSame(AiStatusSchema::HEALTH_DEGRADED, $endpointGroups['governance']['health']);
        self::assertSame('hf-public', $endpointGroups['document']['diagnostics']['provider_name']);
        self::assertSame('hf-public', $endpointGroups['normalization']['diagnostics']['provider_name']);
        self::assertSame('hf-public', $endpointGroups['governance']['diagnostics']['provider_name']);

        Http::assertSent(static fn ($request): bool => $request->url() === 'https://hf.example.test/document/health');
        Http::assertNotSent(static fn ($request): bool => $request->url() === 'https://hf.example.test/document');
    }

    public function testItSupportsMethodAndPathSpecificHealthProbeOverrides(): void
    {
        config()->set('atomy.ai.mode', AiStatusSchema::MODE_PROVIDER);
        config()->set('atomy.ai.provider.name', 'hf-public');
        config()->set('atomy.ai.endpoints.document.uri', 'https://hf.example.test/document');
        config()->set('atomy.ai.endpoints.document.health_path', '/status');
        config()->set('atomy.ai.endpoints.document.health_method', 'POST');

        Http::fake([
            'https://hf.example.test/document/status' => function ($request) {
                if ($request->method() !== 'POST') {
                    return Http::response(['error' => 'wrong-method'], 405);
                }

                return Http::response(['ok' => true], 200);
            },
            '*' => Http::response(['error' => 'unexpected'], 405),
        ]);

        $response = $this->getJson('/api/v1/ai/status');

        $response->assertOk();
        $response->assertJsonPath(
            'data.capability_statuses.document_intelligence.status',
            AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
        );

        Http::assertSent(static function ($request): bool {
            return $request->url() === 'https://hf.example.test/document/status'
                && $request->method() === 'POST';
        });
    }

    public function testItFallsBackGracefullyForMalformedRuntimeConfig(): void
    {
        config()->set('atomy.ai.mode', AiStatusSchema::MODE_PROVIDER);
        config()->set('atomy.ai.provider', 'broken');
        config()->set('atomy.ai.endpoints', 'broken');

        $response = $this->getJson('/api/v1/ai/status');

        $response->assertOk();
        $response->assertJsonPath('data.mode', AiStatusSchema::MODE_PROVIDER);
        $response->assertJsonPath('data.provider_name', 'openrouter');
        self::assertNotEmpty($response->json('data.capability_definitions'));
        self::assertNotEmpty($response->json('data.capability_statuses'));
        self::assertNotEmpty($response->json('data.endpoint_groups'));
    }

    public function testItReturnsStableUnavailablePayloadWhenRuntimeSnapshotThrows(): void
    {
        config()->set('atomy.ai.mode', AiStatusSchema::MODE_PROVIDER);
        config()->set('atomy.ai.provider.key', 'openrouter');

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

        $response = $this->getJson('/api/v1/ai/status');

        $response->assertOk();
        $response->assertJsonPath('data.mode', AiStatusSchema::MODE_PROVIDER);
        $response->assertJsonPath('data.global_health', AiStatusSchema::HEALTH_DEGRADED);
        $response->assertJsonPath('data.provider_name', 'openrouter');
        $this->assertContains('provider_unavailable', $response->json('data.reason_codes'));
        self::assertNotEmpty($response->json('data.capability_definitions'));
        self::assertNotEmpty($response->json('data.capability_statuses'));
        self::assertNotEmpty($response->json('data.endpoint_groups'));
        $response->assertJsonPath(
            'data.capability_statuses.normalization_intelligence.status',
            AiStatusSchema::CAPABILITY_STATUS_DEGRADED,
        );
        $response->assertJsonPath(
            'data.endpoint_groups.0.health',
            AiStatusSchema::HEALTH_UNAVAILABLE,
        );
    }

    public function testItFallsBackToMinimalStaticPayloadWhenCoordinatorSnapshotFails(): void
    {
        config()->set('atomy.ai.mode', AiStatusSchema::MODE_PROVIDER);
        config()->set('atomy.ai.provider.key', ' anthropic ');
        config()->set('atomy.ai.provider.name', '   ');

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
                return 'anthropic';
            }
        });

        $this->app->instance(
            AiStatusCoordinatorInterface::class,
            new readonly class implements AiStatusCoordinatorInterface {
                public function snapshot(
                    string $mode,
                    array $endpointGroupHealthSnapshots,
                    ?\DateTimeImmutable $generatedAt = null,
                ): AiStatusSnapshot {
                    throw new \RuntimeException('coordinator failed');
                }
            },
        );

        $response = $this->getJson('/api/v1/ai/status');

        $response->assertOk();
        $response->assertJsonPath('data.mode', AiStatusSchema::MODE_PROVIDER);
        $response->assertJsonPath('data.global_health', AiStatusSchema::HEALTH_UNAVAILABLE);
        $response->assertJsonPath('data.provider_name', 'anthropic');
        $response->assertJsonPath('data.reason_codes.0', 'provider_unavailable');
        $response->assertJsonCount(0, 'data.capability_definitions');
        $response->assertJsonCount(0, 'data.capability_statuses');
        $response->assertJsonCount(0, 'data.endpoint_groups');
    }
}
