<?php

declare(strict_types=1);

namespace Nexus\Laravel\InsightOperations\Adapters;

use Nexus\InsightOperations\Contracts\DashboardSnapshotPortInterface;
use Nexus\QueryEngine\Contracts\AnalyticsRepositoryInterface;

final readonly class DashboardSnapshotPortAdapter implements DashboardSnapshotPortInterface
{
    public function __construct(private AnalyticsRepositoryInterface $analyticsRepository) {}

    public function snapshot(string $dashboardId, string $tenantId): array
    {
        $history = $this->analyticsRepository->getHistory('dashboard', $tenantId . ':' . $dashboardId, 50);

        return [
            'tenant_id' => $tenantId,
            'dashboard_id' => $dashboardId,
            'captured_at' => gmdate(DATE_ATOM),
            'query_history' => $history,
        ];
    }
}
