<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Rules;

final class DashboardSnapshotRule
{
    public function assert(string $dashboardId, string $tenantId): void
    {
        if ($dashboardId === '') {
            throw new \InvalidArgumentException('dashboardId is required.');
        }

        if ($tenantId === '') {
            throw new \InvalidArgumentException('tenantId is required.');
        }
    }
}
