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
                $this->logger->info("Initiating forecast for model: {$modelId}");
                
                $forecastContext = ['historical_data' => $reportData, 'parameters' => $parameters];
                $jobId = $this->predictionService->predictAsync($modelId, $forecastContext);

                // Polling for completion
                $maxAttempts = $parameters['forecast_max_attempts'] ?? 10;
                $pollIntervalMs = $parameters['forecast_poll_interval_ms'] ?? 100;
                $attempt = 0;
                $status = 'pending';

                while ($attempt < $maxAttempts) {
                    $status = $this->predictionService->getStatus($jobId);
                    if ($status === 'completed' || $status === 'failed') {
                        break;
                    }
                    $attempt++;
                    usleep($pollIntervalMs * 1000);
                }

                if ($status === 'completed') {
                    $predictionResult = $this->predictionService->getPrediction($jobId);

                    if ($predictionResult) {
                        $reportData = [
                            'historical' => $reportData,
                            'forecast' => $predictionResult->getData(),
                            'metadata' => [
                                'confidence' => $predictionResult->getConfidence(),
                                'model_version' => $predictionResult->getModelVersion(),
                                'forecast_status' => 'success',
                            ]
                        ];
                    } else {
                        $this->logger->warning("Forecast result unavailable despite completed status", [
                            'job_id' => $jobId,
                            'model_id' => $modelId,
                            'template_id' => $reportTemplateId,
                        ]);
                        $reportData = [
                            'historical' => $reportData,
                            'forecast' => null,
                            'metadata' => [
                                'forecast_unavailable' => true,
                                'forecast_error' => 'Empty result from prediction service',
                                'forecast_status' => 'failed',
                                'confidence' => null,
                                'model_version' => null,
                            ]
                        ];
                    }
                } else {
                    $this->logger->error("Forecast failed or timed out", [
                        'job_id' => $jobId,
                        'status' => $status,
                        'attempts' => $attempt,
                        'model_id' => $modelId,
                    ]);
                    $reportData = [
                        'historical' => $reportData,
                        'forecast' => null,
                        'metadata' => [
                            'forecast_unavailable' => true,
                            'forecast_error' => ($status === 'failed') ? 'Prediction job failed' : 'Forecast timeout',
                            'forecast_status' => ($status === 'failed') ? 'failed' : 'timeout',
                            'confidence' => null,
                            'model_version' => null,
                        ]
                    ];
                }
            }

            // 3. Render/Export
            $exportResult = $this->exportGenerator->generate($reportData, $deliveryOptions['format'] ?? 'pdf');
            $filePath = $exportResult->getFilePathOrFail();

            // 4. Store
            $storagePath = "reports/" . gmdate('Y/m/d/') . basename($filePath);
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
