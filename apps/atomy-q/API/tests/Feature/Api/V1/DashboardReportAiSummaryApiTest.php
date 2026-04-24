<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Adapters\Ai\Contracts\ProviderInsightClientInterface;
use Nexus\IntelligenceOperations\DTOs\AiCapabilityStatus;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Tests\Feature\Api\Concerns\BindsAiRuntimeStatus;
use Tests\Feature\Api\ApiTestCase;

final class DashboardReportAiSummaryApiTest extends ApiTestCase
{
    use BindsAiRuntimeStatus;

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

        $generate = $this->postJson('/api/v1/dashboard/kpis/generate', [], $this->authHeaders());
        $response = $this->getJson('/api/v1/dashboard/kpis', $this->authHeaders());

        $generate->assertOk();
        $response->assertOk();
        $response->assertJsonPath('data.active_rfqs', 0);
        $response->assertJsonPath('data.ai_summary.available', true);
        $response->assertJsonPath('data.ai_summary.payload.headline', 'Dashboard is on track.');
        $response->assertJsonPath('data.ai_summary.payload.source_facts.active_rfqs', 0);
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
        $response->assertJsonPath('data.active_rfqs', 0);
        $response->assertJsonPath('data.ai_summary.available', false);
        $response->assertJsonPath('data.ai_summary.payload', null);
        $response->assertJsonPath('data.ai_summary.feature_key', 'dashboard_ai_summary');
    }

    public function testReportSpendTrendReturnsProviderSummaryWhenAiIsAvailable(): void
    {
        $this->bindAiRuntimeStatus([
            'reporting_ai_summary' => new AiCapabilityStatus(
                featureKey: 'reporting_ai_summary',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_INSIGHT_INTELLIGENCE,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_INSIGHT,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                messageKey: 'ai.reporting_ai_summary.available',
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
                    'headline' => 'Spend trend is steady.',
                    'summary' => 'No significant monthly variance detected.',
                    'bullets' => ['Monthly period remains consistent.'],
                ];
            }
        });

        $generate = $this->postJson('/api/v1/reports/spend-trend/generate', [], $this->authHeaders());
        $response = $this->getJson('/api/v1/reports/spend-trend', $this->authHeaders());

        $generate->assertOk();
        $response->assertOk();
        $response->assertJsonPath('data.period', 'monthly');
        $response->assertJsonPath('data.series', []);
        $response->assertJsonPath('data.ai_summary.available', true);
        $response->assertJsonPath('data.ai_summary.payload.headline', 'Spend trend is steady.');
        $response->assertJsonPath('data.ai_summary.payload.source_facts.period', 'monthly');
        $response->assertJsonPath('data.ai_summary.payload.source_facts.series', []);
    }

    public function testReportSpendTrendReturnsUnavailableSummaryWithoutLosingData(): void
    {
        $this->bindAiRuntimeStatus([
            'reporting_ai_summary' => new AiCapabilityStatus(
                featureKey: 'reporting_ai_summary',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_INSIGHT_INTELLIGENCE,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_INSIGHT,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                messageKey: 'ai.reporting_ai_summary.unavailable',
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
