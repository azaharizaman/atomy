<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Adapters\Ai\Contracts\AiRuntimeStatusInterface;
use App\Adapters\Ai\Contracts\ProviderInsightClientInterface;
use Illuminate\Support\Str;
use Nexus\IntelligenceOperations\DTOs\AiCapabilityStatus;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Nexus\IntelligenceOperations\DTOs\AiStatusSnapshot;
use Tests\Feature\Api\ApiTestCase;

final class DashboardReportAiSummaryApiTest extends ApiTestCase
{
    private function bindAiRuntimeStatus(array $capabilityStatuses): void
    {
        $snapshot = new AiStatusSnapshot(
            mode: AiStatusSchema::MODE_PROVIDER,
            globalHealth: AiStatusSchema::HEALTH_HEALTHY,
            capabilityDefinitions: [],
            capabilityStatuses: $capabilityStatuses,
            endpointGroupHealthSnapshots: [],
            reasonCodes: [],
            generatedAt: new \DateTimeImmutable('2026-04-24T03:00:00Z'),
        );

        $this->app->instance(AiRuntimeStatusInterface::class, new readonly class($snapshot) implements AiRuntimeStatusInterface {
            public function __construct(private AiStatusSnapshot $snapshot)
            {
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

    public function testDashboardKpisReturnsProviderSummaryWhenAiIsAvailable(): void
    {
        $this->bindAiRuntimeStatus([
            'dashboard_ai_summary' => new AiCapabilityStatus(
                featureKey: 'dashboard_ai_summary',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_INSIGHT_INTELLIGENCE,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_INSIGHT,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                messageKey: 'ai.dashboard_ai_summary.available',
                status: AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
                available: true,
                reasonCodes: ['provider_available'],
                operatorCritical: false,
                diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
            ),
        ]);

        $this->app->instance(ProviderInsightClientInterface::class, new readonly class implements ProviderInsightClientInterface {
            public function summarize(\App\Adapters\Ai\DTOs\InsightSummaryRequest $request): array
            {
                return [
                    'headline' => 'Dashboard is on track.',
                    'highlights' => ['RFQ throughput is stable'],
                    'recommendations' => ['Continue current operating cadence'],
                ];
            }
        });

        $response = $this->getJson('/api/v1/dashboard/kpis', $this->authHeaders());

        $response->assertOk();
        $response->assertJsonPath('active_rfqs', 0);
        $response->assertJsonPath('ai_summary.available', true);
        $response->assertJsonPath('ai_summary.payload.headline', 'Dashboard is on track.');
        $response->assertJsonPath('ai_summary.payload.source_facts.active_rfqs', 0);
    }

    public function testDashboardKpisReturnsUnavailableSummaryWithoutLosingFacts(): void
    {
        $this->bindAiRuntimeStatus([
            'dashboard_ai_summary' => new AiCapabilityStatus(
                featureKey: 'dashboard_ai_summary',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_INSIGHT_INTELLIGENCE,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_INSIGHT,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                messageKey: 'ai.dashboard_ai_summary.unavailable',
                status: AiStatusSchema::CAPABILITY_STATUS_UNAVAILABLE,
                available: false,
                reasonCodes: ['provider_unavailable'],
                operatorCritical: false,
                diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
            ),
        ]);

        $this->app->instance(ProviderInsightClientInterface::class, new readonly class implements ProviderInsightClientInterface {
            public function summarize(\App\Adapters\Ai\DTOs\InsightSummaryRequest $request): array
            {
                throw new \RuntimeException('provider client should not be called when unavailable');
            }
        });

        $response = $this->getJson('/api/v1/dashboard/kpis', $this->authHeaders());

        $response->assertOk();
        $response->assertJsonPath('active_rfqs', 0);
        $response->assertJsonPath('ai_summary.available', false);
        $response->assertJsonPath('ai_summary.payload', null);
        $response->assertJsonPath('ai_summary.feature_key', 'dashboard_ai_summary');
    }

    public function testReportSpendTrendReturnsUnavailableSummaryWithoutLosingData(): void
    {
        $this->bindAiRuntimeStatus([
            'dashboard_ai_summary' => new AiCapabilityStatus(
                featureKey: 'dashboard_ai_summary',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_INSIGHT_INTELLIGENCE,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_INSIGHT,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                messageKey: 'ai.dashboard_ai_summary.unavailable',
                status: AiStatusSchema::CAPABILITY_STATUS_UNAVAILABLE,
                available: false,
                reasonCodes: ['provider_unavailable'],
                operatorCritical: false,
                diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
            ),
        ]);

        $this->app->instance(ProviderInsightClientInterface::class, new readonly class implements ProviderInsightClientInterface {
            public function summarize(\App\Adapters\Ai\DTOs\InsightSummaryRequest $request): array
            {
                throw new \RuntimeException('provider client should not be called when unavailable');
            }
        });

        $response = $this->getJson('/api/v1/reports/spend-trend', $this->authHeaders());

        $response->assertOk();
        $response->assertJsonPath('data.period', 'monthly');
        $response->assertJsonPath('data.series', []);
        $response->assertJsonPath('data.ai_summary.available', false);
        $response->assertJsonPath('data.ai_summary.payload', null);
    }
}
