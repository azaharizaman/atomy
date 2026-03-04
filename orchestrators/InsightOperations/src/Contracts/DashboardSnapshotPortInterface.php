<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Contracts;

use Nexus\InsightOperations\DTOs\DashboardSnapshotDto;

interface DashboardSnapshotPortInterface
{
    public function snapshot(string $dashboardId, string $tenantId): DashboardSnapshotDto;
}
