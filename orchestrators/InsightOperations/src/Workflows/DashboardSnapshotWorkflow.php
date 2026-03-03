<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Workflows;

use Nexus\InsightOperations\Contracts\DashboardSnapshotPortInterface;
use Nexus\InsightOperations\Contracts\InsightStoragePortInterface;
use Nexus\InsightOperations\DTOs\DashboardSnapshotResult;
use Nexus\InsightOperations\Rules\DashboardSnapshotRule;

final readonly class DashboardSnapshotWorkflow
{
    public function __construct(
        private DashboardSnapshotRule $rule,
        private DashboardSnapshotPortInterface $snapshotPort,
        private InsightStoragePortInterface $storagePort,
    ) {}

    public function run(string $dashboardId, string $tenantId): DashboardSnapshotResult
    {
        $this->rule->assert($dashboardId, $tenantId);

        $snapshot = $this->snapshotPort->snapshot($dashboardId, $tenantId);
        $payload = json_encode($snapshot->toArray(), JSON_THROW_ON_ERROR);
        $tenantSegment = rawurlencode($tenantId);
        $dashboardSegment = rawurlencode($dashboardId);

        $path = sprintf(
            'snapshots/%s/%s/%s_%s.json',
            $tenantSegment,
            $dashboardSegment,
            gmdate('YmdHis'),
            substr(str_replace('.', '', (string) microtime(true)), -6)
        );
        $this->storagePort->put($path, $payload);

        return new DashboardSnapshotResult(
            snapshotPath: $path,
            bytes: strlen($payload),
        );
    }
}
