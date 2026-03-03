<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Services;

use Nexus\InsightOperations\Contracts\ReportingPipelineCoordinatorInterface;

/**
 * Compatibility facade retained for callers using src/Services namespace.
 */
final readonly class ReportingCoordinator implements ReportingPipelineCoordinatorInterface
{
    public function __construct(private \Nexus\InsightOperations\Coordinators\ReportingCoordinator $coordinator) {}

    public function runPipeline(string $reportTemplateId, array $parameters = [], array $deliveryOptions = []): string
    {
        return $this->coordinator->runPipeline($reportTemplateId, $parameters, $deliveryOptions);
    }

    public function captureSnapshot(string $dashboardId, string $tenantId): string
    {
        return $this->coordinator->captureSnapshot($dashboardId, $tenantId);
    }
}
