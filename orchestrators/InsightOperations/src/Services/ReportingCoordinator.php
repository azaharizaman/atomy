<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Services;

use Nexus\Export\Contracts\ExportGeneratorInterface;
use Nexus\InsightOperations\Contracts\ReportingPipelineCoordinatorInterface;
use Nexus\MachineLearning\Contracts\PredictionServiceInterface;
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
        private PredictionServiceInterface $predictionService,
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
            // 1. Execute Historical Query
            $historicalResult = $this->queryEngine->executeQuery($reportTemplateId, $parameters);
            $reportData = $historicalResult->getData();

            // 2. Append Forecasted Data if requested
            if ($parameters['include_forecast'] ?? false) {
                $modelId = $parameters['forecast_model_id'] ?? $reportTemplateId;
                $this->logger->info("Appending forecasted data using model: {$modelId}");
                
                // Fetch synchronous prediction (simplified for orchestrator)
                // In a production scenario, this might involve checking async job status
                $forecastContext = ['historical_data' => $reportData, 'parameters' => $parameters];
                $predictionResult = $this->predictionService->getPrediction(
                    $this->predictionService->predictAsync($modelId, $forecastContext)
                );

                if ($predictionResult) {
                    $reportData = [
                        'historical' => $reportData,
                        'forecast' => $predictionResult->getData(),
                        'metadata' => [
                            'confidence' => $predictionResult->getConfidence(),
                            'model_version' => $predictionResult->getModelVersion(),
                        ]
                    ];
                }
            }

            // 3. Render/Export
            $exportResult = $this->exportGenerator->generate($reportData, $deliveryOptions['format'] ?? 'pdf');
            $filePath = $exportResult->getFilePathOrFail();

            // 4. Store
            $storagePath = "reports/" . date('Y/m/d/') . basename($filePath);
            $stream = fopen($filePath, 'r');
            
            if ($stream === false) {
                throw new \RuntimeException("Failed to open report file for reading: {$filePath}");
            }

            try {
                $this->storageDriver->put($storagePath, $stream);
            } finally {
                fclose($stream);
            }

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
