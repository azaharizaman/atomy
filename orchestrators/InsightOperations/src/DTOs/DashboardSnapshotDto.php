<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\DTOs;

final readonly class DashboardSnapshotDto
{
    /**
     * @param array<int, array<string, mixed>> $queryHistory
     */
    public function __construct(
        public string $tenantId,
        public string $dashboardId,
        public string $capturedAt,
        public array $queryHistory,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'dashboard_id' => $this->dashboardId,
            'captured_at' => $this->capturedAt,
            'query_history' => $this->queryHistory,
        ];
    }
}
