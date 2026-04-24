<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Adapters\Ai\Contracts\ProviderInsightClientInterface;
use App\Adapters\Ai\DTOs\InsightSummaryRequest;
use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Api\V1\Concerns\InteractsWithAiAvailability;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Common\Contracts\ClockInterface;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Throwable;

final class DashboardController extends Controller
{
    use ExtractsAuthContext;
    use InteractsWithAiAvailability;

    public function __construct(
        private readonly ProviderInsightClientInterface $insightClient,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * Get dashboard KPIs scoped by tenant.
     *
     * GET /dashboard/kpis
     */
    public function kpis(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $facts = [
            'active_rfqs' => 0,
            'pending_approvals' => 0,
            'total_savings' => 0.0,
            'avg_cycle_time_days' => 0,
        ];

        return response()->json([
            ...$facts,
            'ai_summary' => $this->buildDashboardSummaryEnvelope($tenantId, $facts),
        ]);
    }

    /**
     * Get spend trend data scoped by tenant.
     *
     * GET /dashboard/spend-trend
     */
    public function spendTrend(Request $request): JsonResponse
    {
        $this->tenantId($request);

        return response()->json([
            'data' => [],
        ]);
    }

    /**
     * Get vendor score data scoped by tenant.
     *
     * GET /dashboard/vendor-scores
     */
    public function vendorScores(Request $request): JsonResponse
    {
        $this->tenantId($request);

        return response()->json([
            'data' => [],
        ]);
    }

    /**
     * Get recent activity scoped by tenant.
     *
     * GET /dashboard/recent-activity
     */
    public function recentActivity(Request $request): JsonResponse
    {
        $this->tenantId($request);

        return response()->json([
            'data' => [],
        ]);
    }

    /**
     * Get risk alerts scoped by tenant.
     *
     * GET /dashboard/risk-alerts
     */
    public function riskAlerts(Request $request): JsonResponse
    {
        $this->tenantId($request);

        return response()->json([
            'data' => [],
        ]);
    }

    /**
     * @param array<string, mixed> $facts
     * @return array<string, mixed>
     */
    private function buildDashboardSummaryEnvelope(string $tenantId, array $facts): array
    {
        if (! $this->aiCapabilityAvailable('dashboard_ai_summary')) {
            return $this->unavailableArtifactEnvelope('dashboard_ai_summary');
        }

        try {
            $summary = $this->insightClient->summarize(new InsightSummaryRequest(
                featureKey: 'dashboard_ai_summary',
                tenantId: $tenantId,
                subjectType: 'dashboard_kpis',
                facts: $facts,
            ));
        } catch (Throwable $exception) {
            report($exception);

            return $this->unavailableArtifactEnvelope('dashboard_ai_summary');
        }

        return $this->artifactEnvelope(
            featureKey: 'dashboard_ai_summary',
            payload: $this->providerSummaryPayload($summary, $facts),
            provenance: $this->providerArtifactProvenance($summary, AiStatusSchema::ENDPOINT_GROUP_INSIGHT),
        );
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, scalar|array<string, mixed>|null> $facts
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
     * @param array<string, mixed> $facts
     * @return array<string, mixed>
     */
    private function providerSummaryPayload(array $payload, array $facts): array
    {
        $summary = is_array($payload['payload'] ?? null) ? $payload['payload'] : $payload;
        if (! is_array($summary)) {
            $summary = [];
        }

        $summary['source_facts'] = $facts;

        return $summary;
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
