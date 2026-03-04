<?php

declare(strict_types=1);

namespace Nexus\DataExchangeOperations\Contracts;

interface DataOnboardingCoordinatorInterface
{
    /**
     * Execute a bulk onboarding workflow and return task id.
     *
     * $options keys:
     * - sourcePath: string path or URL to the source data
     * - tenantId: string tenant identifier
     * @param array<string, mixed> $options
     */
    public function onboard(string $sourcePath, string $tenantId, array $options = []): string;

    /**
     * @return array<string, mixed>
     */
    public function getTaskStatus(string $tenantId, string $taskId): array;
}
