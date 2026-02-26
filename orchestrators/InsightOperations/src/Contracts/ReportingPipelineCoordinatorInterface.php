<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Contracts;

/**
 * Interface ReportingPipelineCoordinatorInterface
 *
 * Coordinates automated reporting pipelines: Query -> Render -> Store -> Notify.
 */
interface ReportingPipelineCoordinatorInterface
{
    /**
     * Executes an automated reporting pipeline.
     *
     * @param string $reportTemplateId Template for the report.
     * @param array $parameters Parameters for the query engine.
     * @param array $deliveryOptions Storage and notification settings.
     * @return string The pipeline execution identifier.
     */
    public function runPipeline(string $reportTemplateId, array $parameters = [], array $deliveryOptions = []): string;

    /**
     * Captures a dashboard snapshot for historical analysis.
     *
     * @param string $dashboardId
     * @param string $tenantId
     * @return string Snapshot storage location.
     */
    public function captureSnapshot(string $dashboardId, string $tenantId): string;
}
