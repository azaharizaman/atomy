<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\DTOs;

final readonly class DashboardSnapshotResult
{
    public function __construct(
        public string $snapshotPath,
        public int $bytes,
    ) {}
}
