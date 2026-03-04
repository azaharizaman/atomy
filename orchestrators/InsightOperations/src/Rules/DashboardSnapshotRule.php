<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Rules;

final class DashboardSnapshotRule
{
    public function assert(string $dashboardId, string $tenantId): void
    {
        if (trim($dashboardId) === '') {
            throw new \InvalidArgumentException('dashboardId is required.');
        }

        if (trim($tenantId) === '') {
            throw new \InvalidArgumentException('tenantId is required.');
        }
    }
}
