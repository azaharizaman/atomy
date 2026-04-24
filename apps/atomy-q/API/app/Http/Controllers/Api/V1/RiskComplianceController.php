<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Adapters\Ai\Contracts\ProviderInsightClientInterface;
use App\Adapters\Ai\DTOs\InsightSummaryRequest;
use App\Http\Controllers\Api\V1\Concerns\BuildsAiArtifactEnvelope;
use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Api\V1\Concerns\InteractsWithAiAvailability;
use App\Http\Controllers\Controller;
use App\Models\Rfq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Nexus\Common\Contracts\ClockInterface;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Throwable;

final class RiskComplianceController extends Controller
{
    use BuildsAiArtifactEnvelope;
    use ExtractsAuthContext;
    use InteractsWithAiAvailability;

    public function __construct(
        private readonly ProviderInsightClientInterface $insightClient,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * GET /risk-items?rfqId=:id
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $rfqId = trim((string) $request->query('rfqId'));
        if ($rfqId !== '' && ! $this->rfqExists($tenantId, $rfqId)) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }

        $riskItems = $this->loadRiskItems($tenantId, $rfqId);

        return response()->json([
            'data' => [
                'items' => $riskItems,
                'ai_insights' => $this->readRfqInsightsEnvelope($tenantId, $rfqId, $riskItems),
                'manual_review' => $this->manualReviewEnvelope($riskItems),
            ],
            'meta' => [
                'rfq_id' => $rfqId !== '' ? $rfqId : null,
            ],
        ]);
    }

    /**
     * POST /risk-items/generate
     */
    public function generate(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $rfqId = trim((string) ($request->input('rfq_id') ?? $request->query('rfqId', '')));
        if ($rfqId === '' || ! $this->rfqExists($tenantId, $rfqId)) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }

        $riskItems = $this->loadRiskItems($tenantId, $rfqId);

        return response()->json([
            'data' => [
                'items' => $riskItems,
                'ai_insights' => $this->buildRfqInsightsEnvelope($tenantId, $rfqId, $riskItems),
                'manual_review' => $this->manualReviewEnvelope($riskItems),
            ],
            'meta' => [
                'rfq_id' => $rfqId,
            ],
        ]);
    }

    /**
     * POST /risk-items/:id/escalate
     */
    public function escalate(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'status' => 'escalated',
                'escalated_at' => null,
            ],
        ]);
    }

    /**
     * POST /risk-items/:id/exception
     */
    public function exception(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'status' => 'exception',
                'exception_approved_at' => null,
            ],
        ]);
    }

    /**
     * POST /vendors/:id/sanctions-screening
     */
    public function sanctionsScreening(Request $request, string $vendorId): JsonResponse
    {
        return response()->json([
            'data' => [
                'vendor_id' => $vendorId,
                'screening_status' => 'completed',
                'matches' => [],
            ],
        ]);
    }

    /**
     * GET /vendors/:id/sanctions-history
     */
    public function sanctionsHistory(Request $request, string $vendorId): JsonResponse
    {
        return response()->json([
            'data' => [
                'vendor_id' => $vendorId,
                'history' => [],
            ],
        ]);
    }

    /**
     * GET /vendors/:id/due-diligence
     */
    public function dueDiligence(Request $request, string $vendorId): JsonResponse
    {
        return response()->json([
            'data' => [
                'vendor_id' => $vendorId,
                'items' => [],
                'overall_status' => 'pending',
            ],
        ]);
    }

    /**
     * PATCH /vendors/:id/due-diligence/:itemId
     */
    public function updateDueDiligence(Request $request, string $vendorId, string $itemId): JsonResponse
    {
        return response()->json([
            'data' => [
                'vendor_id' => $vendorId,
                'item_id' => $itemId,
                'status' => 'completed',
            ],
        ]);
    }

    /**
     * @param list<array<string, mixed>> $riskItems
     * @return array<string, mixed>
     */
    private function buildRfqInsightsEnvelope(string $tenantId, string $rfqId, array $riskItems): array
    {
        if ($rfqId === '' || $riskItems === [] || ! $this->aiCapabilityAvailable('rfq_ai_insights')) {
            return $this->unavailableArtifactEnvelope('rfq_ai_insights');
        }

        try {
            $insights = $this->insightClient->summarize(new InsightSummaryRequest(
                featureKey: 'rfq_ai_insights',
                tenantId: $tenantId,
                subjectType: 'risk_items',
                facts: [
                    'rfq_id' => $rfqId,
                    'risk_items' => $riskItems,
                ],
            ));
        } catch (Throwable $exception) {
            report($exception);

            return $this->unavailableArtifactEnvelope('rfq_ai_insights');
        }

        $artifact = $this->artifactEnvelope(
            featureKey: 'rfq_ai_insights',
            payload: $this->providerSummaryPayload($insights, $riskItems, $rfqId),
            provenance: $this->providerArtifactProvenance($insights, AiStatusSchema::ENDPOINT_GROUP_INSIGHT),
        );

        Cache::put($this->rfqInsightsCacheKey($tenantId, $rfqId, $riskItems), $artifact, now()->addMinutes(5));

        return $artifact;
    }

    /**
     * @param list<array<string, mixed>> $riskItems
     * @return array<string, mixed>
     */
    private function readRfqInsightsEnvelope(string $tenantId, string $rfqId, array $riskItems): array
    {
        if ($rfqId === '' || $riskItems === []) {
            return $this->unavailableArtifactEnvelope('rfq_ai_insights');
        }

        /** @var array<string, mixed>|null $cached */
        $cached = Cache::get($this->rfqInsightsCacheKey($tenantId, $rfqId, $riskItems));

        return is_array($cached) ? $cached : $this->unavailableArtifactEnvelope('rfq_ai_insights');
    }

    /**
     * @param list<array<string, mixed>> $riskItems
     * @return array<string, mixed>
     */
    private function manualReviewEnvelope(array $riskItems): array
    {
        $capabilityStatus = $this->aiCapabilityStatus('governance_manual_review');
        $statusSnapshot = $this->aiStatusSnapshot();

        return [
            'feature_key' => 'governance_manual_review',
            'capability_group' => AiStatusSchema::CAPABILITY_GROUP_GOVERNANCE_INTELLIGENCE,
            'available' => $capabilityStatus?->available ?? true,
            'status' => $capabilityStatus?->status ?? AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
            'manual_continuity' => 'available',
            'reason_codes' => $capabilityStatus?->reasonCodes ?? ['ai_not_required'],
            'diagnostics' => $capabilityStatus?->diagnostics ?? ['mode' => $statusSnapshot->mode],
            'pending_items' => count($riskItems),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<array<string, mixed>> $riskItems
     * @return array<string, mixed>
     */
    private function providerSummaryPayload(array $payload, array $riskItems, string $rfqId): array
    {
        $content = $this->unwrapProviderPayload($payload);
        $summary = [];

        foreach (['headline', 'summary', 'body', 'bullets'] as $key) {
            if (array_key_exists($key, $content)) {
                $summary[$key] = $content[$key];
            }
        }

        $summary['source_facts'] = [
            'rfq_id' => $rfqId,
            'risk_items' => $riskItems,
        ];

        return $summary;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadRiskItems(string $tenantId, string $rfqId): array
    {
        unset($tenantId, $rfqId);

        return [];
    }

    private function rfqExists(string $tenantId, string $rfqId): bool
    {
        return Rfq::query()
            ->where('tenant_id', strtolower(trim($tenantId)))
            ->where('id', strtolower(trim($rfqId)))
            ->exists();
    }

    /**
     * @param list<array<string, mixed>> $riskItems
     * @return array<string, mixed>
     */
    private function rfqInsightsCacheKey(string $tenantId, string $rfqId, array $riskItems): string
    {
        $encoded = json_encode($riskItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return 'ai:risk-items:' . strtolower(trim($tenantId)) . ':' . strtolower(trim($rfqId)) . ':' . hash(
            'sha256',
            is_string($encoded) ? $encoded : serialize($riskItems),
        );
    }

    protected function aiArtifactCapabilityGroup(): string
    {
        return AiStatusSchema::CAPABILITY_GROUP_INSIGHT_INTELLIGENCE;
    }

    protected function aiArtifactEndpointGroup(): string
    {
        return AiStatusSchema::ENDPOINT_GROUP_INSIGHT;
    }
}
