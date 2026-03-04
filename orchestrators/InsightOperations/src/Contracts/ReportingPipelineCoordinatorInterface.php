<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Contracts;

interface ReportingPipelineCoordinatorInterface
{
    /**
     * @param array<string, mixed> $parameters
     * @param array<string, mixed> $deliveryOptions
     */
    public function runPipeline(string $reportTemplateId, array $parameters = [], array $deliveryOptions = []): string;

    public function captureSnapshot(string $dashboardId, string $tenantId): string;
}
