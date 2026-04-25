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

/**
 * Report API controller (Section 21).
 *
 * Handles KPIs, spend reports, export, schedules, and runs.
 */
final class ReportController extends Controller
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
     * Get KPI metrics.
     *
     * GET /reports/kpis
     */
    public function kpis(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $facts = [
            'total_spend' => 0,
            'active_rfqs' => 0,
            'savings' => 0,
        ];

        return response()->json([
            'data' => [
                ...$facts,
                'ai_summary' => $this->buildReportSummaryEnvelope($tenantId, 'report_kpis', $facts, false),
            ],
        ]);
    }

    /**
     * Get spend trend.
     *
     * GET /reports/spend-trend
     */
    public function spendTrend(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $facts = [
            'series' => [],
            'period' => 'monthly',
        ];

        return response()->json([
            'data' => [
                ...$facts,
                'ai_summary' => $this->buildReportSummaryEnvelope($tenantId, 'report_spend_trend', $facts, false),
            ],
        ]);
    }

    /**
     * Get spend by category.
     *
     * GET /reports/spend-by-category
     */
    public function spendByCategory(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $facts = [
            'categories' => [],
        ];

        return response()->json([
            'data' => [
                ...$facts,
                'ai_summary' => $this->buildReportSummaryEnvelope($tenantId, 'report_spend_by_category', $facts, false),
            ],
        ]);
    }

    /**
     * POST /reports/kpis/generate
     */
    public function generateKpisSummary(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $facts = [
            'total_spend' => 0,
            'active_rfqs' => 0,
            'savings' => 0,
        ];

        return response()->json([
            'data' => [
                ...$facts,
                'ai_summary' => $this->buildReportSummaryEnvelope($tenantId, 'report_kpis', $facts, true),
            ],
        ]);
    }

    /**
     * POST /reports/spend-trend/generate
     */
    public function generateSpendTrendSummary(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $facts = [
            'series' => [],
            'period' => 'monthly',
        ];

        return response()->json([
            'data' => [
                ...$facts,
                'ai_summary' => $this->buildReportSummaryEnvelope($tenantId, 'report_spend_trend', $facts, true),
            ],
        ]);
    }

    /**
     * POST /reports/spend-by-category/generate
     */
    public function generateSpendByCategorySummary(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $facts = [
            'categories' => [],
        ];

        return response()->json([
            'data' => [
                ...$facts,
                'ai_summary' => $this->buildReportSummaryEnvelope($tenantId, 'report_spend_by_category', $facts, true),
            ],
        ]);
    }

    /**
     * Export report.
     *
     * POST /reports/export
     */
    public function export(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'download_url' => 'https://example.com/reports/export-stub',
                'expires_at' => now()->addHours(24)->toIso8601String(),
            ],
        ]);
    }

    /**
     * List report schedules.
     *
     * GET /reports/schedules
     */
    public function schedules(Request $request): JsonResponse
    {
        $params = $this->paginationParams($request);

        return response()->json([
            'data' => [],
            'meta' => [
                'current_page' => $params['page'],
                'per_page' => $params['per_page'],
                'total' => 0,
            ],
        ]);
    }

    /**
     * Create report schedule.
     *
     * POST /reports/schedules
     */
    public function createSchedule(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => 'stub-schedule-id',
                'frequency' => 'weekly',
            ],
        ], 201);
    }

    /**
     * Update report schedule.
     *
     * PUT /reports/schedules/:id
     */
    public function updateSchedule(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'frequency' => 'weekly',
            ],
        ]);
    }

    /**
     * Delete report schedule.
     *
     * DELETE /reports/schedules/:id
     */
    public function destroySchedule(Request $request, string $id): JsonResponse
    {
        return response()->json([], 204);
    }

    /**
     * Run schedule immediately.
     *
     * POST /reports/schedules/:id/run-now
     */
    public function runScheduleNow(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'run_id' => 'stub-run-id',
                'status' => 'queued',
            ],
        ]);
    }

    /**
     * List report runs.
     *
     * GET /reports/runs
     */
    public function runs(Request $request): JsonResponse
    {
        $params = $this->paginationParams($request);

        return response()->json([
            'data' => [],
            'meta' => [
                'current_page' => $params['page'],
                'per_page' => $params['per_page'],
                'total' => 0,
            ],
        ]);
    }

    /**
     * Download report run output.
     *
     * GET /reports/runs/:id/download
     */
    public function downloadRun(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'download_url' => 'https://example.com/reports/runs/' . $id . '/download',
                'expires_at' => now()->addHours(24)->toIso8601String(),
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $facts
     * @return array<string, mixed>
     */
    private function buildReportSummaryEnvelope(string $tenantId, string $subjectType, array $facts, bool $generate): array
    {
        $featureKey = 'reporting_ai_summary';
        $cacheKey = $this->reportSummaryCacheKey($tenantId, $subjectType, $facts);

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
                subjectType: $subjectType,
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
    private function reportSummaryCacheKey(string $tenantId, string $subjectType, array $facts): string
    {
        return 'ai:reports:' . strtolower(trim($tenantId)) . ':' . $subjectType . ':' . $this->factsHash($facts);
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
