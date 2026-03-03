<?php

declare(strict_types=1);

namespace Nexus\Laravel\InsightOperations\Adapters;

use Nexus\InsightOperations\Contracts\DashboardSnapshotPortInterface;
use Nexus\InsightOperations\DTOs\DashboardSnapshotDto;
use Nexus\QueryEngine\Contracts\AnalyticsRepositoryInterface;

final readonly class DashboardSnapshotPortAdapter implements DashboardSnapshotPortInterface
{
    public function __construct(private AnalyticsRepositoryInterface $analyticsRepository) {}

    public function snapshot(string $dashboardId, string $tenantId): DashboardSnapshotDto
    {
        $history = $this->analyticsRepository->getHistory('dashboard', $tenantId . ':' . $dashboardId, 50);

        return new DashboardSnapshotDto(
            tenantId: $tenantId,
            dashboardId: $dashboardId,
            capturedAt: gmdate(DATE_ATOM),
            queryHistory: $history,
        );
    }
}
