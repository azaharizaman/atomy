<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Adapters\Ai\Contracts\ProviderInsightClientInterface;
use App\Adapters\Ai\DTOs\InsightSummaryRequest;
use App\Http\Controllers\Api\V1\Concerns\InteractsWithAiAvailability;
use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Common\Contracts\ClockInterface;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Throwable;

final class RiskComplianceController extends Controller
{
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
        $riskItems = [];

        return response()->json([
            'data' => [
                'items' => $riskItems,
                'ai_insights' => $this->buildRfqInsightsEnvelope($tenantId, $rfqId, $riskItems),
                'manual_review' => $this->manualReviewEnvelope($riskItems),
            ],
            'meta' => [
                'rfq_id' => $rfqId !== '' ? $rfqId : null,
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
        if ($rfqId === '' || ! $this->aiCapabilityAvailable('rfq_ai_insights')) {
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

        return $this->artifactEnvelope(
            featureKey: 'rfq_ai_insights',
            payload: $this->providerSummaryPayload($insights, $riskItems, $rfqId),
            provenance: $this->providerArtifactProvenance($insights, AiStatusSchema::ENDPOINT_GROUP_INSIGHT),
        );
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
        $summary = is_array($payload['payload'] ?? null) ? $payload['payload'] : $payload;
        if (! is_array($summary)) {
            $summary = [];
        }

        $summary['source_facts'] = [
            'rfq_id' => $rfqId,
            'risk_items' => $riskItems,
        ];

        return $summary;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function artifactEnvelope(string $featureKey, array $payload, ?array $provenance = null): array
    {
        return [
            'feature_key' => $featureKey,
            'capability_group' => AiStatusSchema::CAPABILITY_GROUP_INSIGHT_INTELLIGENCE,
            'available' => true,
            'status' => AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
            'manual_continuity' => 'available',
            'payload' => $payload,
            'provenance' => $provenance,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function unavailableArtifactEnvelope(string $featureKey): array
    {
        $capabilityStatus = $this->aiCapabilityStatus($featureKey);
        $statusSnapshot = $this->aiStatusSnapshot();

        return [
            'feature_key' => $featureKey,
            'capability_group' => AiStatusSchema::CAPABILITY_GROUP_INSIGHT_INTELLIGENCE,
            'available' => false,
            'status' => $capabilityStatus?->status ?? AiStatusSchema::CAPABILITY_STATUS_UNAVAILABLE,
            'manual_continuity' => 'available',
            'fallback_ui_mode' => $capabilityStatus?->fallbackUiMode ?? AiStatusSchema::FALLBACK_UI_MODE_SHOW_UNAVAILABLE_MESSAGE,
            'message_key' => $capabilityStatus?->messageKey ?? 'ai.capability.unavailable',
            'reason_codes' => $capabilityStatus?->reasonCodes ?? $statusSnapshot->reasonCodes,
            'diagnostics' => $capabilityStatus?->diagnostics ?? ['mode' => $statusSnapshot->mode],
            'payload' => null,
            'provenance' => [
                'source' => 'deterministic',
                'endpoint_group' => AiStatusSchema::ENDPOINT_GROUP_INSIGHT,
                'provider_name' => $this->providerName(),
                'generated_at' => $this->clock->now()->format(DATE_ATOM),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function providerArtifactProvenance(array $payload, string $endpointGroup): array
    {
        $provenance = is_array($payload['provenance'] ?? null) ? $payload['provenance'] : [];

        return array_replace($provenance, [
            'source' => 'provider',
            'provider_name' => $this->providerName(),
            'endpoint_group' => $endpointGroup,
            'generated_at' => $provenance['generated_at'] ?? $this->clock->now()->format(DATE_ATOM),
        ]);
    }

    private function providerName(): ?string
    {
        $providerName = config('atomy.ai.provider.name');
        if (is_string($providerName)) {
            $providerName = trim($providerName);
            if ($providerName !== '') {
                return $providerName;
            }
        }

        $providerKey = config('atomy.ai.provider.key');
        if (! is_string($providerKey)) {
            return null;
        }

        $providerKey = trim($providerKey);

        return $providerKey === '' ? null : strtolower($providerKey);
    }
}
