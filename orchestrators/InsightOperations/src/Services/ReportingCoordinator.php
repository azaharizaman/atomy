<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Services;

use Nexus\Export\Contracts\ExportGeneratorInterface;
use Nexus\InsightOperations\Contracts\ReportingPipelineCoordinatorInterface;
use Nexus\Notifier\Contracts\NotificationManagerInterface;
use Nexus\QueryEngine\Contracts\AnalyticsRepositoryInterface;
use Nexus\Storage\Contracts\StorageDriverInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ReportingCoordinator
 *
 * Orchestrates the reporting pipeline from data extraction to delivery.
 */
final readonly class ReportingCoordinator implements ReportingPipelineCoordinatorInterface
{
    public function __construct(
        private AnalyticsRepositoryInterface $queryEngine,
        private ExportGeneratorInterface $exportGenerator,
        private StorageDriverInterface $storageDriver,
        private NotificationManagerInterface $notificationManager,
        private LoggerInterface $logger
    ) {}

    /**
     * @inheritDoc
     */
    public function runPipeline(string $reportTemplateId, array $parameters = [], array $deliveryOptions = []): string
    {
        $this->logger->info("Executing reporting pipeline", [
            'template' => $reportTemplateId,
            'params' => $parameters
        ]);

        try {
            // 1. Execute Query
            $data = $this->queryEngine->executeQuery($reportTemplateId, $parameters);

            // 2. Render/Export
            $filePath = $this->exportGenerator->generate($data, $deliveryOptions['format'] ?? 'pdf');

            // 3. Store
            $storagePath = "reports/" . date('Y/m/d/') . basename($filePath);
            $this->storageDriver->put($storagePath, file_get_contents($filePath));

            // 4. Notify
            if (!empty($deliveryOptions['recipients'])) {
                // $this->notificationManager->sendMany($deliveryOptions['recipients'], ...);
            }

            return $storagePath;
        } catch (\Throwable $e) {
            $this->logger->error("Reporting pipeline failed", [
                'template' => $reportTemplateId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function captureSnapshot(string $dashboardId, string $tenantId): string
    {
        $this->logger->info("Capturing dashboard snapshot", [
            'dashboard' => $dashboardId,
            'tenant' => $tenantId
        ]);

        // Logic to extract dashboard state and store it
        $snapshotPath = "snapshots/{$tenantId}/{$dashboardId}/" . time() . ".json";
        $this->storageDriver->put($snapshotPath, json_encode(['data' => 'placeholder']));

        return $snapshotPath;
    }
}
