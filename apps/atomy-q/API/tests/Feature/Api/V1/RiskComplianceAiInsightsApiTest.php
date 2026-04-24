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

final class RiskComplianceAiInsightsApiTest extends ApiTestCase
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

    public function testIndexReturnsProviderInsightsAndManualReview(): void
    {
        $this->bindAiRuntimeStatus([
            'rfq_ai_insights' => new AiCapabilityStatus(
                featureKey: 'rfq_ai_insights',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_INSIGHT_INTELLIGENCE,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_INSIGHT,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                messageKey: 'ai.rfq_ai_insights.available',
                status: AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
                available: true,
                reasonCodes: ['provider_available'],
                operatorCritical: false,
                diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
            ),
            'governance_manual_review' => new AiCapabilityStatus(
                featureKey: 'governance_manual_review',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_GOVERNANCE_INTELLIGENCE,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_GOVERNANCE,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_HIDE_AI_CONTROLS,
                messageKey: 'ai.governance_manual_review.available',
                status: AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
                available: true,
                reasonCodes: ['ai_not_required'],
                operatorCritical: true,
                diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
            ),
        ]);

        $this->app->instance(ProviderInsightClientInterface::class, new readonly class implements ProviderInsightClientInterface {
            public function summarize(\App\Adapters\Ai\DTOs\InsightSummaryRequest $request): array
            {
                return [
                    'headline' => 'Risk profile is manageable.',
                    'next_steps' => ['Review supplier documentation'],
                ];
            }
        });

        $rfqId = Str::lower((string) Str::ulid());
        $response = $this->getJson('/api/v1/risk-items?rfqId=' . $rfqId, $this->authHeaders());

        $response->assertOk();
        $response->assertJsonPath('data.ai_insights.available', true);
        $response->assertJsonPath('data.ai_insights.payload.headline', 'Risk profile is manageable.');
        $response->assertJsonPath('data.manual_review.available', true);
        $response->assertJsonPath('meta.rfq_id', $rfqId);
    }

    public function testIndexReturnsUnavailableInsightsWithoutDisablingManualReview(): void
    {
        $this->bindAiRuntimeStatus([
            'rfq_ai_insights' => new AiCapabilityStatus(
                featureKey: 'rfq_ai_insights',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_INSIGHT_INTELLIGENCE,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_INSIGHT,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_SHOW_MANUAL_CONTINUITY_BANNER,
                messageKey: 'ai.rfq_ai_insights.unavailable',
                status: AiStatusSchema::CAPABILITY_STATUS_UNAVAILABLE,
                available: false,
                reasonCodes: ['provider_unavailable'],
                operatorCritical: false,
                diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
            ),
            'governance_manual_review' => new AiCapabilityStatus(
                featureKey: 'governance_manual_review',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_GOVERNANCE_INTELLIGENCE,
                endpointGroup: AiStatusSchema::ENDPOINT_GROUP_GOVERNANCE,
                fallbackUiMode: AiStatusSchema::FALLBACK_UI_MODE_HIDE_AI_CONTROLS,
                messageKey: 'ai.governance_manual_review.available',
                status: AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
                available: true,
                reasonCodes: ['ai_not_required'],
                operatorCritical: true,
                diagnostics: ['mode' => AiStatusSchema::MODE_PROVIDER],
            ),
        ]);

        $this->app->instance(ProviderInsightClientInterface::class, new readonly class implements ProviderInsightClientInterface {
            public function summarize(\App\Adapters\Ai\DTOs\InsightSummaryRequest $request): array
            {
                throw new \RuntimeException('provider client should not be called when unavailable');
            }
        });

        $rfqId = Str::lower((string) Str::ulid());
        $response = $this->getJson('/api/v1/risk-items?rfqId=' . $rfqId, $this->authHeaders());

        $response->assertOk();
        $response->assertJsonPath('meta.rfq_id', $rfqId);
        $response->assertJsonPath('data.ai_insights.available', false);
        $response->assertJsonPath('data.ai_insights.payload', null);
        $response->assertJsonPath('data.manual_review.available', true);
        $response->assertJsonPath('data.manual_review.status', AiStatusSchema::CAPABILITY_STATUS_AVAILABLE);
    }
}
