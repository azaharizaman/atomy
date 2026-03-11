<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    use ExtractsAuthContext;

    /**
     * Get dashboard KPIs scoped by tenant.
     *
     * GET /dashboard/kpis
     */
    public function kpis(Request $request): JsonResponse
    {
        $this->tenantId($request);

        return response()->json([
            'active_rfqs' => 0,
            'pending_approvals' => 0,
            'total_savings' => 0.0,
            'avg_cycle_time_days' => 0,
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
}
