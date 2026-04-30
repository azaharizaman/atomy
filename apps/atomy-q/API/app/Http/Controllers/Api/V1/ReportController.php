<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\InsightOperations\Contracts\ReportingInsightCoordinatorInterface;

/**
 * Report API controller (Section 21).
 *
 * Handles KPIs, spend reports, export, schedules, and runs.
 */
final class ReportController extends Controller
{
    use ExtractsAuthContext;

    public function __construct(
        private readonly ReportingInsightCoordinatorInterface $reportingInsightCoordinator,
    ) {}

    /**
     * Get KPI metrics.
     *
     * GET /reports/kpis
     */
    public function kpis(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        return response()->json(
            $this->reportingInsightCoordinator
                ->show($tenantId, "report_kpis")
                ->toResponseArray(),
        );
    }

    /**
     * Get spend trend.
     *
     * GET /reports/spend-trend
     */
    public function spendTrend(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        return response()->json(
            $this->reportingInsightCoordinator
                ->show($tenantId, "report_spend_trend")
                ->toResponseArray(),
        );
    }

    /**
     * Get spend by category.
     *
     * GET /reports/spend-by-category
     */
    public function spendByCategory(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        return response()->json(
            $this->reportingInsightCoordinator
                ->show($tenantId, "report_spend_by_category")
                ->toResponseArray(),
        );
    }

    /**
     * POST /reports/kpis/generate
     */
    public function generateKpisSummary(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $userId = $this->userId($request);

        return response()->json(
            $this->reportingInsightCoordinator
                ->generate($tenantId, "report_kpis", $userId)
                ->toResponseArray(),
        );
    }

    /**
     * POST /reports/spend-trend/generate
     */
    public function generateSpendTrendSummary(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $userId = $this->userId($request);

        return response()->json(
            $this->reportingInsightCoordinator
                ->generate($tenantId, "report_spend_trend", $userId)
                ->toResponseArray(),
        );
    }

    /**
     * POST /reports/spend-by-category/generate
     */
    public function generateSpendByCategorySummary(
        Request $request,
    ): JsonResponse {
        $tenantId = $this->tenantId($request);
        $userId = $this->userId($request);

        return response()->json(
            $this->reportingInsightCoordinator
                ->generate($tenantId, "report_spend_by_category", $userId)
                ->toResponseArray(),
        );
    }

    /**
     * Export report.
     *
     * POST /reports/export
     */
    public function export(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        throw new \DomainException(
            sprintf(
                'Report export not yet implemented for tenant "%s".',
                $tenantId,
            ),
        );
    }

    /**
     * List report schedules.
     *
     * GET /reports/schedules
     */
    public function schedules(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        throw new \DomainException(
            sprintf(
                'Report schedules are not yet implemented for tenant "%s".',
                $tenantId,
            ),
        );
    }

    /**
     * Create report schedule.
     *
     * POST /reports/schedules
     */
    public function createSchedule(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        throw new \DomainException(
            sprintf(
                'Report schedule creation is not yet implemented for tenant "%s".',
                $tenantId,
            ),
        );
    }

    /**
     * Update report schedule.
     *
     * PUT /reports/schedules/:id
     */
    public function updateSchedule(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        throw new \DomainException(
            sprintf(
                'Report schedule update is not yet implemented for tenant "%s" (schedule "%s").',
                $tenantId,
                $id,
            ),
        );
    }

    /**
     * Delete report schedule.
     *
     * DELETE /reports/schedules/:id
     */
    public function destroySchedule(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        throw new \DomainException(
            sprintf(
                'Report schedule deletion is not yet implemented for tenant "%s" (schedule "%s").',
                $tenantId,
                $id,
            ),
        );
    }

    /**
     * Run schedule immediately.
     *
     * POST /reports/schedules/:id/run-now
     */
    public function runScheduleNow(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        throw new \DomainException(
            sprintf(
                'Report schedule run-now is not yet implemented for tenant "%s" (schedule "%s").',
                $tenantId,
                $id,
            ),
        );
    }

    /**
     * List report runs.
     *
     * GET /reports/runs
     */
    public function runs(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        throw new \DomainException(
            sprintf(
                'Report runs listing is not yet implemented for tenant "%s".',
                $tenantId,
            ),
        );
    }

    /**
     * Download report run output.
     *
     * GET /reports/runs/:id/download
     */
    public function downloadRun(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        throw new \DomainException(
            sprintf(
                'Report run download is not yet implemented for tenant "%s" (run "%s").',
                $tenantId,
                $id,
            ),
        );
    }
}
