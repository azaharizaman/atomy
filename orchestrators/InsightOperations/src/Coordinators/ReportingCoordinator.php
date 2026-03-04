<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Coordinators;

use Nexus\InsightOperations\Contracts\ReportingPipelineCoordinatorInterface;
use Nexus\InsightOperations\DTOs\ReportingPipelineRequest;
use Nexus\InsightOperations\Workflows\DashboardSnapshotWorkflow;
use Nexus\InsightOperations\Workflows\ReportingPipelineWorkflow;
use Psr\Log\LoggerInterface;

final readonly class ReportingCoordinator implements ReportingPipelineCoordinatorInterface
{
    public function __construct(
        private ReportingPipelineWorkflow $pipelineWorkflow,
        private DashboardSnapshotWorkflow $snapshotWorkflow,
        private LoggerInterface $logger,
    ) {}

    public function runPipeline(string $reportTemplateId, array $parameters = [], array $deliveryOptions = []): string
    {
        $rawPipelineId = $deliveryOptions['pipeline_id'] ?? null;
        $pipelineId = is_string($rawPipelineId) && trim($rawPipelineId) !== ''
            ? $rawPipelineId
            : sprintf('pipe_%s', bin2hex(random_bytes(10)));

        $result = $this->pipelineWorkflow->run(new ReportingPipelineRequest(
            pipelineId: $pipelineId,
            reportTemplateId: $reportTemplateId,
            parameters: $parameters,
            deliveryOptions: $deliveryOptions,
        ));

        $this->logger->info('Reporting pipeline completed.', [
            'pipeline_id' => $result->pipelineId,
            'report_template_id' => $reportTemplateId,
            'storage_path' => $result->storagePath,
            'metadata' => $result->metadata,
        ]);

        return $result->storagePath;
    }

    public function captureSnapshot(string $dashboardId, string $tenantId): string
    {
        $result = $this->snapshotWorkflow->run($dashboardId, $tenantId);

        $this->logger->info('Dashboard snapshot captured.', [
            'dashboard_id' => $dashboardId,
            'tenant_id' => $tenantId,
            'snapshot_path' => $result->snapshotPath,
            'bytes' => $result->bytes,
        ]);

        return $result->snapshotPath;
    }
}
