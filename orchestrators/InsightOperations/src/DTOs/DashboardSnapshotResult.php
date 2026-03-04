<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\DTOs;

final readonly class DashboardSnapshotResult
{
    public function __construct(
        public string $snapshotPath,
        public int $bytes,
    ) {
        if ($this->bytes < 0) {
            throw new \InvalidArgumentException('bytes must be non-negative.');
        }
    }
}
