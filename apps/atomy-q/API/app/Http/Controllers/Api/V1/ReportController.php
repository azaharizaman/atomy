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

/**
 * Report API controller (Section 21).
 *
 * Handles KPIs, spend reports, export, schedules, and runs.
 */
final class ReportController extends Controller
{
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
                'ai_summary' => $this->buildReportSummaryEnvelope($tenantId, 'report_kpis', $facts),
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
                'ai_summary' => $this->buildReportSummaryEnvelope($tenantId, 'report_spend_trend', $facts),
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
                'ai_summary' => $this->buildReportSummaryEnvelope($tenantId, 'report_spend_by_category', $facts),
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
    private function buildReportSummaryEnvelope(string $tenantId, string $subjectType, array $facts): array
    {
        if (! $this->aiCapabilityAvailable('dashboard_ai_summary')) {
            return $this->unavailableArtifactEnvelope('dashboard_ai_summary');
        }

        try {
            $summary = $this->insightClient->summarize(new InsightSummaryRequest(
                featureKey: 'dashboard_ai_summary',
                tenantId: $tenantId,
                subjectType: $subjectType,
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
