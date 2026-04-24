<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Adapters\Ai\Contracts\ProviderInsightClientInterface;
use App\Models\Rfq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nexus\IntelligenceOperations\DTOs\AiCapabilityStatus;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Tests\Feature\Api\Concerns\BindsAiRuntimeStatus;
use Tests\Feature\Api\ApiTestCase;

final class RiskComplianceAiInsightsApiTest extends ApiTestCase
{
    use BindsAiRuntimeStatus;
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

    public function testIndexReturnsUnavailableInsightsAndManualReviewForExistingRfqWithoutRiskItems(): void
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
                throw new \RuntimeException('provider client should not be called without persisted risk items');
            }
        });

        $rfq = $this->createRfq($this->tenantId);
        $rfqId = strtolower((string) $rfq->id);
        $response = $this->getJson('/api/v1/risk-items?rfqId=' . $rfqId, $this->authHeaders());

        $response->assertOk();
        $response->assertJsonPath('data.items', []);
        $response->assertJsonPath('data.ai_insights.available', false);
        $response->assertJsonPath('data.ai_insights.payload', null);
        $response->assertJsonPath('data.manual_review.available', true);
        $response->assertJsonPath('data.manual_review.pending_items', 0);
        $response->assertJsonPath('meta.rfq_id', $rfqId);
    }

    public function testIndexReturnsNotFoundForCrossTenantRfq(): void
    {
        $rfq = $this->createRfq('tenant-other');
        $rfqId = strtolower((string) $rfq->id);
        $response = $this->getJson('/api/v1/risk-items?rfqId=' . $rfqId, $this->authHeaders());

        $response->assertNotFound();
    }

    private function createRfq(string $tenantId): Rfq
    {
        /** @var Rfq $rfq */
        $rfq = Rfq::query()->create([
            'tenant_id' => strtolower($tenantId),
            'rfq_number' => 'RFQ-' . date('Y') . '-' . substr((string) uniqid('', true), -6),
            'title' => 'Risk Test RFQ',
            'status' => 'published',
            'owner_id' => 'user-test',
            'estimated_value' => 1000,
            'savings_percentage' => 0,
            'submission_deadline' => now()->addDay(),
            'closing_date' => now()->addDays(2),
        ]);

        return $rfq;
    }
}
