<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Adapters\Ai\Contracts\AiRuntimeStatusInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Nexus\IntelligenceOperations\DTOs\AiCapabilityStatus;
use Nexus\IntelligenceOperations\DTOs\AiStatusSnapshot;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
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
            ],
        ]);
    }

    public function testItReturnsDisabledStatusWhenAiModeIsOff(): void
    {
        config()->set('atomy.ai.mode', AiStatusSchema::MODE_OFF);
        config()->set('atomy.ai.provider.name', 'hf-public');
        config()->set('atomy.ai.endpoints.document.uri', 'https://hf.example.test/document');

        $response = $this->getJson('/api/v1/ai/status');

        $response->assertOk();
        $response->assertJsonPath('data.mode', AiStatusSchema::MODE_OFF);
        $response->assertJsonPath('data.global_health', AiStatusSchema::HEALTH_DISABLED);
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
            AiStatusSchema::CAPABILITY_STATUS_UNAVAILABLE,
        );
        $this->assertContains('deterministic_fallback_mode', $response->json('data.reason_codes'));
    }

    public function testItReflectsMixedProviderEndpointHealth(): void
    {
        config()->set('atomy.ai.mode', AiStatusSchema::MODE_PROVIDER);
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
        $response->assertJsonPath('data.global_health', AiStatusSchema::HEALTH_DEGRADED);
        $response->assertJsonPath(
            'data.capability_statuses.document_intelligence.status',
            AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
        );
        $response->assertJsonPath(
            'data.capability_statuses.normalization_intelligence.status',
            AiStatusSchema::CAPABILITY_STATUS_UNAVAILABLE,
        );
        $response->assertJsonPath(
            'data.capability_statuses.governance_intelligence.status',
            AiStatusSchema::CAPABILITY_STATUS_DEGRADED,
        );

        $endpointGroups = collect($response->json('data.endpoint_groups'))->keyBy('endpoint_group');
        self::assertSame(AiStatusSchema::HEALTH_HEALTHY, $endpointGroups['document']['health']);
        self::assertSame(AiStatusSchema::HEALTH_UNAVAILABLE, $endpointGroups['normalization']['health']);
        self::assertSame(AiStatusSchema::HEALTH_DEGRADED, $endpointGroups['governance']['health']);

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
        self::assertNotEmpty($response->json('data.endpoint_groups'));
    }

    public function testItReturnsStableUnavailablePayloadWhenRuntimeSnapshotThrows(): void
    {
        config()->set('atomy.ai.mode', AiStatusSchema::MODE_PROVIDER);

        $this->app->instance(AiRuntimeStatusInterface::class, new readonly class implements AiRuntimeStatusInterface {
            public function snapshot(): AiStatusSnapshot
            {
                throw new \RuntimeException('runtime probe failed');
            }

            public function capabilityStatus(string $featureKey): ?AiCapabilityStatus
            {
                return null;
            }
        });

        $response = $this->getJson('/api/v1/ai/status');

        $response->assertOk();
        $response->assertJsonPath('data.mode', AiStatusSchema::MODE_PROVIDER);
        $response->assertJsonPath('data.global_health', AiStatusSchema::HEALTH_UNAVAILABLE);
        $response->assertJsonPath('data.reason_codes.0', 'provider_unavailable');
        $response->assertJsonPath('data.capability_definitions', []);
        $response->assertJsonPath('data.capability_statuses', []);
        $response->assertJsonPath('data.endpoint_groups', []);
    }
}
