<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Tests\Feature;

use PHPUnit\Framework\TestCase;
use Nexus\QuotationIntelligence\Coordinators\QuotationIntelligenceCoordinator;
use Nexus\QuotationIntelligence\Contracts\SemanticMapperInterface;
use Nexus\QuotationIntelligence\Contracts\QuoteNormalizationServiceInterface;
use Nexus\QuotationIntelligence\Contracts\RiskAssessmentServiceInterface;
use Nexus\QuotationIntelligence\DTOs\NormalizedQuoteLine;
use Nexus\Document\Contracts\ContentProcessorInterface;
use Nexus\Document\Contracts\DocumentRepositoryInterface;
use Nexus\Document\Contracts\DocumentInterface;
use Nexus\Document\ValueObjects\ContentAnalysisResult;
use Nexus\Document\ValueObjects\DocumentType;
use Psr\Log\LoggerInterface;

final class EndToEndQuoteProcessingTest extends TestCase
{
    public function test_full_pipeline_orchestration(): void
    {
        // 1. Arrange - Mock all dependencies for the coordinator
        $processor = $this->createMock(ContentProcessorInterface::class);
        $repo = $this->createMock(DocumentRepositoryInterface::class);
        $mapper = $this->createMock(SemanticMapperInterface::class);
        $normService = $this->createMock(QuoteNormalizationServiceInterface::class);
        $riskService = $this->createMock(RiskAssessmentServiceInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        // Setup mock document
        $document = $this->createMock(DocumentInterface::class);
        $document->method('getStoragePath')->willReturn('/tmp/quote.pdf');
        $repo->method('findById')->willReturn($document);

        // Setup REAL ContentAnalysisResult instead of mock
        $analysis = new ContentAnalysisResult(
            predictedType: DocumentType::PDF,
            confidenceScore: 0.95,
            extractedMetadata: [
                'lines' => [
                    ['description' => 'Laptop', 'quantity' => 1, 'unit' => 'EA', 'unit_price' => 1000]
                ]
            ],
            containsPII: false,
            suggestedTags: [],
            rawAnalysis: []
        );
        $processor->method('analyze')->willReturn($analysis);

        // Setup mock intelligence services
        $mapper->method('mapToTaxonomy')->willReturn(['code' => '43211503', 'confidence' => 0.99]);
        $normService->method('normalizeQuantity')->willReturn(1.0);
        $normService->method('normalizePrice')->willReturn(1000.0);
        $riskService->method('assess')->willReturn([]);

        $coordinator = new QuotationIntelligenceCoordinator(
            $processor, $repo, $mapper, $normService, $riskService, $logger
        );

        // 2. Act
        $result = $coordinator->processQuote('tenant-1', 'doc-123');

        // 3. Assert
        $this->assertCount(1, $result['lines']);
        $this->assertSame('43211503', $result['lines'][0]['taxonomy_code']);
        $this->assertCount(0, $result['risks']);
    }
}
