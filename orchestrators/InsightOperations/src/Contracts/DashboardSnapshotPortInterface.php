<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Contracts;

interface DashboardSnapshotPortInterface
{
    /**
     * @return array<string, mixed>
     */
    public function snapshot(string $dashboardId, string $tenantId): array;
}
