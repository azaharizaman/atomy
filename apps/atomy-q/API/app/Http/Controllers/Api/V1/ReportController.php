<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Report API controller (Section 21).
 *
 * Handles KPIs, spend reports, export, schedules, and runs.
 */
final class ReportController extends Controller
{
    use ExtractsAuthContext;

    /**
     * Get KPI metrics.
     *
     * GET /reports/kpis
     */
    public function kpis(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'total_spend' => 0,
                'active_rfqs' => 0,
                'savings' => 0,
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
        return response()->json([
            'data' => [
                'series' => [],
                'period' => 'monthly',
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
        return response()->json([
            'data' => [
                'categories' => [],
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
}
