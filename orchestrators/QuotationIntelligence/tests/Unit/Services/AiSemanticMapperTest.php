<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Nexus\QuotationIntelligence\Services\AiSemanticMapper;
use Nexus\MachineLearning\Contracts\PredictionServiceInterface;
use Nexus\MachineLearning\Contracts\PredictionResultInterface;
use Psr\Log\LoggerInterface;

final class AiSemanticMapperTest extends TestCase
{
    private $predictionService;
    private $logger;
    private $service;

    protected function setUp(): void
    {
        $this->predictionService = $this->createMock(PredictionServiceInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new AiSemanticMapper(
            $this->predictionService,
            $this->logger
        );
    }

    public function test_maps_description_to_unspcs_code(): void
    {
        // 1. Arrange
        $prediction = $this->createMock(PredictionResultInterface::class);
        $prediction->method('getMetadata')->willReturn(['taxonomy_code' => '43211503']);
        $prediction->method('getConfidenceScore')->willReturn(0.98);
        $prediction->method('getModelVersion')->willReturn('1.0.0');

        $this->predictionService->expects($this->once())
            ->method('predictAsync')
            ->with('procurement_taxonomy_unspcs_v25', ['text' => 'laptop', 'tenant_id' => 'T1'])
            ->willReturn('job-123');

        $this->predictionService->expects($this->once())
            ->method('getPrediction')
            ->with('job-123')
            ->willReturn($prediction);

        // 2. Act
        $result = $this->service->mapToTaxonomy('laptop', 'T1');

        // 3. Assert
        $this->assertSame('43211503', $result['code']);
        $this->assertSame(0.98, $result['confidence']);
    }

    public function test_returns_pending_if_not_ready(): void
    {
        // 1. Arrange
        $this->predictionService->method('predictAsync')->willReturn('job-456');
        $this->predictionService->method('getPrediction')->willReturn(null);

        // 2. Act
        $result = $this->service->mapToTaxonomy('laptop', 'T1');

        // 3. Assert
        $this->assertSame('PENDING', $result['code']);
    }
}
