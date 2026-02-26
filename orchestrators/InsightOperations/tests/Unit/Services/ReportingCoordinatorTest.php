<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Tests\Unit\Services;

use Nexus\Export\Contracts\ExportGeneratorInterface;
use Nexus\Export\ValueObjects\ExportResult;
use Nexus\InsightOperations\Services\ReportingCoordinator;
use Nexus\MachineLearning\Contracts\PredictionResultInterface;
use Nexus\MachineLearning\Contracts\PredictionServiceInterface;
use Nexus\Notifier\Contracts\NotificationManagerInterface;
use Nexus\QueryEngine\Contracts\AnalyticsRepositoryInterface;
use Nexus\QueryEngine\Contracts\QueryResultInterface;
use Nexus\Storage\Contracts\StorageDriverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ReportingCoordinatorTest extends TestCase
{
    private readonly AnalyticsRepositoryInterface&MockObject $queryEngine;
    private readonly PredictionServiceInterface&MockObject $predictionService;
    private readonly ExportGeneratorInterface&MockObject $exportGenerator;
    private readonly StorageDriverInterface&MockObject $storageDriver;
    private readonly NotificationManagerInterface&MockObject $notificationManager;
    private readonly LoggerInterface&MockObject $logger;
    private readonly ReportingCoordinator $coordinator;

    protected function setUp(): void
    {
        $this->queryEngine = $this->createMock(AnalyticsRepositoryInterface::class);
        $this->predictionService = $this->createMock(PredictionServiceInterface::class);
        $this->exportGenerator = $this->createMock(ExportGeneratorInterface::class);
        $this->storageDriver = $this->createMock(StorageDriverInterface::class);
        $this->notificationManager = $this->createMock(NotificationManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->coordinator = new ReportingCoordinator(
            $this->queryEngine,
            $this->predictionService,
            $this->exportGenerator,
            $this->storageDriver,
            $this->notificationManager,
            $this->logger
        );
    }

    public function test_it_merges_historical_and_forecasted_data(): void
    {
        $params = ['include_forecast' => true, 'forecast_model_id' => 'sales_model'];
        
        $historicalData = ['2023' => 1000, '2024' => 1200];
        $forecastData = ['2025' => 1500];

        $queryResult = $this->createMock(QueryResultInterface::class);
        $queryResult->method('getData')->willReturn($historicalData);

        $this->queryEngine->expects($this->once())
            ->method('executeQuery')
            ->willReturn($queryResult);

        $this->predictionService->expects($this->once())
            ->method('predictAsync')
            ->willReturn('job_123');

        $this->predictionService->expects($this->exactly(2))
            ->method('getStatus')
            ->with('job_123')
            ->willReturnOnConsecutiveCalls('processing', 'completed');

        $predictionResult = $this->createMock(PredictionResultInterface::class);
        $predictionResult->method('getData')->willReturn($forecastData);
        $predictionResult->method('getConfidence')->willReturn(0.92);
        $predictionResult->method('getModelVersion')->willReturn('v1.0');

        $this->predictionService->expects($this->once())
            ->method('getPrediction')
            ->with('job_123')
            ->willReturn($predictionResult);

        $expectedMergedData = [
            'historical' => $historicalData,
            'forecast' => $forecastData,
            'metadata' => [
                'confidence' => 0.92,
                'model_version' => 'v1.0',
                'forecast_status' => 'success',
            ]
        ];

        $exportResult = $this->createMock(ExportResult::class);
        $exportResult->method('getFilePathOrFail')->willReturn('/tmp/report.pdf');

        $this->exportGenerator->expects($this->once())
            ->method('generate')
            ->with($expectedMergedData, 'pdf')
            ->willReturn($exportResult);

        $this->storageDriver->expects($this->once())
            ->method('put');

        $result = $this->coordinator->runPipeline('test_report', $params);
        
        $this->assertStringContainsString('report.pdf', $result);
    }

    public function test_it_handles_forecast_failure(): void
    {
        $params = ['include_forecast' => true, 'forecast_model_id' => 'sales_model'];
        $historicalData = ['2023' => 1000];

        $queryResult = $this->createMock(QueryResultInterface::class);
        $queryResult->method('getData')->willReturn($historicalData);

        $this->queryEngine->method('executeQuery')->willReturn($queryResult);

        $this->predictionService->method('predictAsync')->willReturn('job_failed');
        $this->predictionService->method('getStatus')->willReturn('failed');

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Forecast failed or timed out'));

        $expectedDataWithFailure = [
            '2023' => 1000,
            'forecast_unavailable' => true,
            'forecast_error' => 'Prediction job failed'
        ];

        $exportResult = $this->createMock(ExportResult::class);
        $exportResult->method('getFilePathOrFail')->willReturn('/tmp/report.pdf');

        $this->exportGenerator->expects($this->once())
            ->method('generate')
            ->with($expectedDataWithFailure, 'pdf')
            ->willReturn($exportResult);

        $this->coordinator->runPipeline('test_report', $params);
    }
}
