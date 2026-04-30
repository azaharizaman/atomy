<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\InsightOperations\Coordinators\DashboardInsightCoordinator;

final class DashboardController extends Controller
{
    use ExtractsAuthContext;

    public function __construct(
        private readonly DashboardInsightCoordinator $dashboardInsightCoordinator,
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

        return response()->json($this->dashboardInsightCoordinator->show($tenantId)->toResponseArray());
    }

    /**
     * POST /dashboard/kpis/generate
     */
    public function generateKpisSummary(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $userId = $this->userId($request);

        return response()->json($this->dashboardInsightCoordinator->generate($tenantId, $userId)->toResponseArray());
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

}
