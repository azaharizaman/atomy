<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Adapters\Ai\Contracts\ProviderInsightClientInterface;
use App\Adapters\Ai\DTOs\InsightSummaryRequest;
use App\Models\QuoteSubmission;
use App\Models\RiskItem;
use App\Models\Vendor;
use App\Models\VendorFinding;
use App\Models\VendorInvitation;
use App\Models\Rfq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Nexus\IntelligenceOperations\DTOs\AiCapabilityStatus;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Tests\Feature\Api\Concerns\BindsAiRuntimeStatus;
use Tests\Feature\Api\ApiTestCase;

final class RiskComplianceAiInsightsApiTest extends ApiTestCase
{
    use BindsAiRuntimeStatus;
    use RefreshDatabase;

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

        $rfq = $this->createRfq($this->tenantId, [
            'submission_deadline' => now()->addDays(10),
            'closing_date' => now()->addDays(12),
        ]);
        $rfqId = strtolower((string) $rfq->id);
        $response = $this->getJson('/api/v1/risk-items?rfqId=' . $rfqId, $this->authHeaders());

        $response->assertOk();
        $response->assertJsonPath('data.risk_items', []);
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

    public function testIndexReturnsTenantScopedFunctionalRiskItems(): void
    {
        $rfq = $this->createRfq($this->tenantId, [
            'submission_deadline' => now()->subDay(),
            'closing_date' => now()->addDays(10),
        ]);
        $vendor = $this->createVendor($this->tenantId);
        VendorInvitation::query()->create([
            'tenant_id' => $this->tenantId,
            'rfq_id' => (string) $rfq->id,
            'vendor_id' => (string) $vendor->id,
            'vendor_email' => 'risk-vendor@example.test',
            'vendor_name' => 'Risk Vendor',
            'status' => 'pending',
            'invited_at' => now(),
        ]);
        VendorFinding::query()->create([
            'tenant_id' => $this->tenantId,
            'vendor_id' => (string) $vendor->id,
            'domain' => 'risk',
            'issue_type' => 'financial_watch',
            'severity' => 'high',
            'status' => 'open',
            'opened_at' => now()->subDays(5),
        ]);
        QuoteSubmission::query()->create([
            'tenant_id' => $this->tenantId,
            'rfq_id' => (string) $rfq->id,
            'vendor_id' => (string) $vendor->id,
            'vendor_name' => 'Risk Vendor',
            'status' => 'needs_review',
            'warnings_count' => 4,
            'errors_count' => 0,
            'submitted_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/risk-items?rfqId=' . strtolower((string) $rfq->id), $this->authHeaders());

        $response->assertOk();
        $response->assertJsonPath('data.risk_items.0.domain', 'risk');
        $response->assertJsonPath('data.risk_items.0.severity', 'high');
        $response->assertJsonPath('data.risk_items.0.status', 'open');
        $this->assertIsString($response->json('data.risk_items.0.title'));
        $this->assertIsString($response->json('data.risk_items.0.source'));
        $this->assertIsString($response->json('data.risk_items.0.source_id'));
        $response->assertJsonPath('data.manual_review.pending_items', 3);
        $response->assertJsonPath('data.ai_insights.reason_codes.0', 'no_cached_ai_artifact');
    }

    public function testGenerateSendsNonEmptyRiskFactsToProvider(): void
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
        ]);
        $rfq = $this->createRfq($this->tenantId, [
            'submission_deadline' => now()->subDay(),
            'closing_date' => now()->subDay(),
        ]);
        $spy = new RiskInsightClientSpy(['headline' => 'RFQ risk requires attention.']);
        $this->app->instance(ProviderInsightClientInterface::class, $spy);

        $response = $this->postJson('/api/v1/risk-items/generate', [
            'rfq_id' => strtolower((string) $rfq->id),
        ], $this->authHeaders());

        $response->assertOk();
        $response->assertJsonPath('data.ai_insights.available', true);
        $response->assertJsonPath('data.ai_insights.payload.headline', 'RFQ risk requires attention.');
        self::assertSame(1, $spy->callCount);
        self::assertSame('rfq_ai_insights', $spy->lastRequest?->featureKey);
        self::assertNotEmpty($spy->lastRequest?->facts['risk_items'] ?? []);
        self::assertSame(strtolower((string) $rfq->id), $spy->lastRequest?->facts['rfq_id'] ?? null);
    }

    public function testRiskItemEscalationAndExceptionUpdateTenantScopedRecords(): void
    {
        $rfq = $this->createRfq($this->tenantId);
        $riskItem = $this->createRiskItem($this->tenantId, (string) $rfq->id);
        $otherRiskItem = $this->createRiskItem('tenant-other', (string) $this->createRfq('tenant-other')->id);

        $crossTenant = $this->postJson('/api/v1/risk-items/' . $otherRiskItem->id . '/escalate', [], $this->authHeaders());
        $escalated = $this->postJson('/api/v1/risk-items/' . $riskItem->id . '/escalate', [], $this->authHeaders());
        $exception = $this->postJson('/api/v1/risk-items/' . $riskItem->id . '/exception', [], $this->authHeaders());

        $crossTenant->assertNotFound();
        $escalated->assertOk();
        $escalated->assertJsonPath('data.status', 'escalated');
        $exception->assertOk();
        $exception->assertJsonPath('data.status', 'exception');
        $this->assertDatabaseHas('risk_items', [
            'id' => (string) $riskItem->id,
            'tenant_id' => $this->tenantId,
            'status' => 'exception',
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createRfq(string $tenantId, array $overrides = []): Rfq
    {
        /** @var Rfq $rfq */
        $rfq = Rfq::query()->create(array_merge([
            'tenant_id' => strtolower($tenantId),
            'rfq_number' => 'RFQ-' . date('Y') . '-' . substr((string) uniqid('', true), -6),
            'title' => 'Risk Test RFQ',
            'status' => 'published',
            'owner_id' => 'user-test',
            'estimated_value' => 1000,
            'savings_percentage' => 0,
            'submission_deadline' => now()->addDay(),
            'closing_date' => now()->addDays(2),
        ], $overrides));

        return $rfq;
    }

    private function createVendor(string $tenantId): Vendor
    {
        /** @var Vendor $vendor */
        $vendor = Vendor::query()->create([
            'tenant_id' => strtolower($tenantId),
            'legal_name' => 'Risk Vendor ' . Str::upper(Str::random(6)),
            'display_name' => 'Risk Vendor',
            'registration_number' => 'RISK-' . Str::upper(Str::random(8)),
            'tax_id' => 'TAX-' . Str::upper(Str::random(8)),
            'country_of_registration' => 'MY',
            'primary_contact_name' => 'Risk Contact',
            'primary_contact_email' => 'risk-contact-' . Str::lower(Str::random(8)) . '@example.test',
            'primary_contact_phone' => '+60123456789',
            'status' => 'approved',
        ]);

        return $vendor;
    }

    private function createRiskItem(string $tenantId, string $rfqId): RiskItem
    {
        /** @var RiskItem $riskItem */
        $riskItem = RiskItem::query()->create([
            'tenant_id' => strtolower($tenantId),
            'rfq_id' => strtolower($rfqId),
            'severity' => 'high',
            'title' => 'Persisted risk item',
            'description' => 'Requires procurement review',
            'source' => 'manual_review',
            'status' => 'open',
        ]);

        return $riskItem;
    }
}

final class RiskInsightClientSpy implements ProviderInsightClientInterface
{
    public int $callCount = 0;
    public ?InsightSummaryRequest $lastRequest = null;

    /**
     * @param array<string, mixed> $response
     */
    public function __construct(private readonly array $response)
    {
    }

    public function summarize(InsightSummaryRequest $request): array
    {
        $this->callCount++;
        $this->lastRequest = $request;

        return $this->response;
    }
}
