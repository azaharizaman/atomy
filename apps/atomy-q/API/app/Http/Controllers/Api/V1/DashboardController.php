<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Adapters\Ai\Contracts\ProviderInsightClientInterface;
use App\Adapters\Ai\DTOs\InsightSummaryRequest;
use App\Http\Controllers\Api\V1\Concerns\BuildsAiArtifactEnvelope;
use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Api\V1\Concerns\InteractsWithAiAvailability;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Nexus\Common\Contracts\ClockInterface;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Throwable;

final class DashboardController extends Controller
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
            'data' => [
                ...$facts,
                'ai_summary' => $this->buildDashboardSummaryEnvelope($tenantId, $facts, false),
            ],
        ]);
    }

    /**
     * POST /dashboard/kpis/generate
     */
    public function generateKpisSummary(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $facts = [
            'active_rfqs' => 0,
            'pending_approvals' => 0,
            'total_savings' => 0.0,
            'avg_cycle_time_days' => 0,
        ];

        return response()->json([
            'data' => [
                ...$facts,
                'ai_summary' => $this->buildDashboardSummaryEnvelope($tenantId, $facts, true),
            ],
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
    private function buildDashboardSummaryEnvelope(string $tenantId, array $facts, bool $generate): array
    {
        $featureKey = 'dashboard_ai_summary';
        $cacheKey = $this->dashboardSummaryCacheKey($tenantId, $facts);
        if (! $generate) {
            /** @var array<string, mixed>|null $cached */
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }

            return $this->unavailableArtifactEnvelope($featureKey);
        }

        if (! $this->aiCapabilityAvailable($featureKey)) {
            return $this->unavailableArtifactEnvelope($featureKey);
        }

        try {
            $summary = $this->insightClient->summarize(new InsightSummaryRequest(
                featureKey: $featureKey,
                tenantId: $tenantId,
                subjectType: 'dashboard_kpis',
                facts: $facts,
            ));
        } catch (Throwable $exception) {
            report($exception);

            return $this->unavailableArtifactEnvelope($featureKey);
        }

        $artifact = $this->artifactEnvelope(
            featureKey: $featureKey,
            payload: $this->providerSummaryPayload($summary, $facts),
            provenance: $this->providerArtifactProvenance($summary, AiStatusSchema::ENDPOINT_GROUP_INSIGHT),
        );

        Cache::put($cacheKey, $artifact, now()->addMinutes(5));

        return $artifact;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function providerSummaryPayload(array $payload, array $facts): array
    {
        $summary = $this->unwrapProviderPayload($payload);
        $summary['source_facts'] = $facts;

        return $summary;
    }

    /**
     * @param array<string, mixed> $facts
     */
    private function dashboardSummaryCacheKey(string $tenantId, array $facts): string
    {
        return 'ai:dashboard:kpis:' . strtolower(trim($tenantId)) . ':' . $this->factsHash($facts);
    }

    /**
     * @param array<string, mixed> $facts
     */
    private function factsHash(array $facts): string
    {
        $encoded = json_encode($facts, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash('sha256', is_string($encoded) ? $encoded : serialize($facts));
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
