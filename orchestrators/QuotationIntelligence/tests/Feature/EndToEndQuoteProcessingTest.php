<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Tests\Feature;

use PHPUnit\Framework\TestCase;
use Nexus\QuotationIntelligence\Coordinators\QuotationIntelligenceCoordinator;
use Nexus\QuotationIntelligence\Contracts\OrchestratorContentProcessorInterface;
use Nexus\QuotationIntelligence\Contracts\OrchestratorDocumentRepositoryInterface;
use Nexus\QuotationIntelligence\Contracts\OrchestratorTenantRepositoryInterface;
use Nexus\QuotationIntelligence\Contracts\OrchestratorProcurementManagerInterface;
use Nexus\QuotationIntelligence\Contracts\QuotationDocumentInterface;
use Nexus\QuotationIntelligence\Contracts\OrchestratorTenantInterface;
use Nexus\QuotationIntelligence\Contracts\OrchestratorRequisitionInterface;
use Nexus\QuotationIntelligence\Contracts\OrchestratorRequisitionLineInterface;
use Nexus\QuotationIntelligence\Contracts\SemanticMapperInterface;
use Nexus\QuotationIntelligence\Contracts\QuoteNormalizationServiceInterface;
use Nexus\QuotationIntelligence\Contracts\CommercialTermsExtractorInterface;
use Nexus\QuotationIntelligence\Contracts\RiskAssessmentServiceInterface;
use Nexus\QuotationIntelligence\Exceptions\DocumentAccessDeniedException;
use Nexus\QuotationIntelligence\Exceptions\InvalidNormalizationContextException;
use Nexus\QuotationIntelligence\Exceptions\MissingRfqContextException;
use Nexus\QuotationIntelligence\Exceptions\SemanticMappingException;
use Nexus\QuotationIntelligence\Exceptions\TenantContextNotFoundException;
use Nexus\QuotationIntelligence\Exceptions\UomNormalizationException;
use Nexus\Document\ValueObjects\ContentAnalysisResult;
use Nexus\Document\ValueObjects\DocumentType;
use Psr\Log\LoggerInterface;

final class EndToEndQuoteProcessingTest extends TestCase
{
    public function test_throws_when_document_tenant_does_not_match(): void
    {
        $processor = $this->createMock(OrchestratorContentProcessorInterface::class);
        $repo = $this->createMock(OrchestratorDocumentRepositoryInterface::class);
        $tenantRepository = $this->createMock(OrchestratorTenantRepositoryInterface::class);
        $procurementManager = $this->createMock(OrchestratorProcurementManagerInterface::class);
        $mapper = $this->createMock(SemanticMapperInterface::class);
        $normService = $this->createMock(QuoteNormalizationServiceInterface::class);
        $termsExtractor = $this->createMock(CommercialTermsExtractorInterface::class);
        $riskService = $this->createMock(RiskAssessmentServiceInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $document = $this->createMock(QuotationDocumentInterface::class);
        $document->method('getTenantId')->willReturn('tenant-2');
        $repo->method('findById')->willReturn($document);

        $coordinator = new QuotationIntelligenceCoordinator(
            $processor,
            $repo,
            $tenantRepository,
            $procurementManager,
            $mapper,
            $normService,
            $termsExtractor,
            $riskService,
            $logger
        );

        $this->expectException(DocumentAccessDeniedException::class);
        $coordinator->processQuote('tenant-1', 'doc-123');
    }

    public function test_throws_when_rfq_context_is_missing(): void
    {
        $processor = $this->createMock(OrchestratorContentProcessorInterface::class);
        $repo = $this->createMock(OrchestratorDocumentRepositoryInterface::class);
        $tenantRepository = $this->createMock(OrchestratorTenantRepositoryInterface::class);
        $procurementManager = $this->createMock(OrchestratorProcurementManagerInterface::class);
        $mapper = $this->createMock(SemanticMapperInterface::class);
        $normService = $this->createMock(QuoteNormalizationServiceInterface::class);
        $termsExtractor = $this->createMock(CommercialTermsExtractorInterface::class);
        $riskService = $this->createMock(RiskAssessmentServiceInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $document = $this->createMock(QuotationDocumentInterface::class);
        $document->method('getTenantId')->willReturn('tenant-1');
        $document->method('getMetadata')->willReturn([]);
        $repo->method('findById')->willReturn($document);

        $tenant = $this->createMock(OrchestratorTenantInterface::class);
        $tenant->method('getCurrency')->willReturn('USD');
        $tenantRepository->method('findById')->willReturn($tenant);

        $coordinator = new QuotationIntelligenceCoordinator(
            $processor,
            $repo,
            $tenantRepository,
            $procurementManager,
            $mapper,
            $normService,
            $termsExtractor,
            $riskService,
            $logger
        );

        $this->expectException(MissingRfqContextException::class);
        $coordinator->processQuote('tenant-1', 'doc-123');
    }

    public function test_throws_when_tenant_context_is_missing(): void
    {
        $processor = $this->createMock(OrchestratorContentProcessorInterface::class);
        $repo = $this->createMock(OrchestratorDocumentRepositoryInterface::class);
        $tenantRepository = $this->createMock(OrchestratorTenantRepositoryInterface::class);
        $procurementManager = $this->createMock(OrchestratorProcurementManagerInterface::class);
        $mapper = $this->createMock(SemanticMapperInterface::class);
        $normService = $this->createMock(QuoteNormalizationServiceInterface::class);
        $termsExtractor = $this->createMock(CommercialTermsExtractorInterface::class);
        $riskService = $this->createMock(RiskAssessmentServiceInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $document = $this->createMock(QuotationDocumentInterface::class);
        $document->method('getTenantId')->willReturn('tenant-1');
        $repo->method('findById')->willReturn($document);

        $tenantRepository->method('findById')->willReturn(null);

        $coordinator = new QuotationIntelligenceCoordinator(
            $processor,
            $repo,
            $tenantRepository,
            $procurementManager,
            $mapper,
            $normService,
            $termsExtractor,
            $riskService,
            $logger
        );

        $this->expectException(TenantContextNotFoundException::class);
        $coordinator->processQuote('tenant-1', 'doc-123');
    }

    public function test_throws_when_fx_lock_date_is_invalid(): void
    {
        $processor = $this->createMock(OrchestratorContentProcessorInterface::class);
        $repo = $this->createMock(OrchestratorDocumentRepositoryInterface::class);
        $tenantRepository = $this->createMock(OrchestratorTenantRepositoryInterface::class);
        $procurementManager = $this->createMock(OrchestratorProcurementManagerInterface::class);
        $mapper = $this->createMock(SemanticMapperInterface::class);
        $normService = $this->createMock(QuoteNormalizationServiceInterface::class);
        $termsExtractor = $this->createMock(CommercialTermsExtractorInterface::class);
        $riskService = $this->createMock(RiskAssessmentServiceInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $document = $this->createMock(QuotationDocumentInterface::class);
        $document->method('getTenantId')->willReturn('tenant-1');
        $document->method('getMetadata')->willReturn([
            'rfq_id' => 'rfq-1',
            'fx_lock_date' => '2026-99-99',
        ]);
        $repo->method('findById')->willReturn($document);

        $tenant = $this->createMock(OrchestratorTenantInterface::class);
        $tenant->method('getCurrency')->willReturn('USD');
        $tenantRepository->method('findById')->willReturn($tenant);

        $line = $this->createMock(OrchestratorRequisitionLineInterface::class);
        $line->method('getUnit')->willReturn('EA');
        $requisition = $this->createMock(OrchestratorRequisitionInterface::class);
        $requisition->method('getLines')->willReturn([$line]);
        $procurementManager->method('getRequisition')->with('rfq-1')->willReturn($requisition);

        $coordinator = new QuotationIntelligenceCoordinator(
            $processor,
            $repo,
            $tenantRepository,
            $procurementManager,
            $mapper,
            $normService,
            $termsExtractor,
            $riskService,
            $logger
        );

        $this->expectException(InvalidNormalizationContextException::class);
        $coordinator->processQuote('tenant-1', 'doc-123');
    }

    public function test_full_pipeline_orchestration(): void
    {
        // 1. Arrange - Mock all dependencies for the coordinator
        $processor = $this->createMock(OrchestratorContentProcessorInterface::class);
        $repo = $this->createMock(OrchestratorDocumentRepositoryInterface::class);
        $tenantRepository = $this->createMock(OrchestratorTenantRepositoryInterface::class);
        $procurementManager = $this->createMock(OrchestratorProcurementManagerInterface::class);
        $mapper = $this->createMock(SemanticMapperInterface::class);
        $normService = $this->createMock(QuoteNormalizationServiceInterface::class);
        $termsExtractor = $this->createMock(CommercialTermsExtractorInterface::class);
        $riskService = $this->createMock(RiskAssessmentServiceInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        // Setup mock document
        $document = $this->createMock(QuotationDocumentInterface::class);
        $document->method('getTenantId')->willReturn('tenant-1');
        $document->method('getMetadata')->willReturn([
            'rfq_id' => 'rfq-1',
            'fx_lock_date' => '2026-03-01',
        ]);
        $document->method('getStoragePath')->willReturn('/tmp/quote.pdf');
        $repo->method('findById')->willReturn($document);

        // Setup tenant context
        $tenant = $this->createMock(OrchestratorTenantInterface::class);
        $tenant->method('getCurrency')->willReturn('USD');
        $tenantRepository->method('findById')->with('tenant-1')->willReturn($tenant);

        // Setup requisition context
        $line = $this->createMock(OrchestratorRequisitionLineInterface::class);
        $line->method('getUnit')->willReturn('EA');
        $requisition = $this->createMock(OrchestratorRequisitionInterface::class);
        $requisition->method('getLines')->willReturn([$line]);
        $procurementManager->method('getRequisition')->with('rfq-1')->willReturn($requisition);

        // Setup REAL ContentAnalysisResult instead of mock
        $analysis = new ContentAnalysisResult(
            predictedType: DocumentType::PDF,
            confidenceScore: 0.95,
            extractedMetadata: [
                'lines' => [
                    [
                        'rfq_line_id' => 'rfq-line-1',
                        'description' => 'Laptop',
                        'quantity' => 1,
                        'unit' => 'EA',
                        'unit_price' => 1000,
                    ]
                ]
            ],
            containsPII: false,
            suggestedTags: [],
            rawAnalysis: []
        );
        $processor->method('analyze')->willReturn($analysis);

        // Setup mock intelligence services
        $mapper->method('mapToTaxonomy')->willReturn(['code' => '43211503', 'confidence' => 0.99, 'version' => '1.0.0']);
        $mapper->method('validateCode')->willReturn(true);
        $normService->method('normalizeQuantity')->willReturn(1.0);
        $normService->expects($this->once())
            ->method('normalizePrice')
            ->with(
                1000.0,
                'USD',
                'USD',
                $this->callback(static fn(?\DateTimeImmutable $date): bool => $date?->format('Y-m-d') === '2026-03-01')
            )
            ->willReturn(1000.0);
        $riskService->method('assess')->willReturn([]);
        $termsExtractor->method('extract')->willReturn([
            'incoterm' => 'DDP',
            'payment_days' => 30,
            'lead_time_days' => 14,
            'warranty_months' => 24,
        ]);

        $coordinator = new QuotationIntelligenceCoordinator(
            $processor,
            $repo,
            $tenantRepository,
            $procurementManager,
            $mapper,
            $normService,
            $termsExtractor,
            $riskService,
            $logger
        );

        // 2. Act
        $result = $coordinator->processQuote('tenant-1', 'doc-123');

        // 3. Assert
        $this->assertCount(1, $result['lines']);
        $this->assertSame('43211503', $result['lines'][0]['taxonomy_code']);
        $this->assertSame(
            '2026-03-01',
            $result['lines'][0]['metadata']['normalization_context']['fx_lock_date']
        );
        $this->assertSame('DDP', $result['lines'][0]['metadata']['commercial_terms']['incoterm']);
        $this->assertCount(0, $result['risks']);
    }

    public function test_throws_when_requisition_not_found_for_rfq(): void
    {
        $processor = $this->createMock(OrchestratorContentProcessorInterface::class);
        $repo = $this->createMock(OrchestratorDocumentRepositoryInterface::class);
        $tenantRepository = $this->createMock(OrchestratorTenantRepositoryInterface::class);
        $procurementManager = $this->createMock(OrchestratorProcurementManagerInterface::class);
        $mapper = $this->createMock(SemanticMapperInterface::class);
        $normService = $this->createMock(QuoteNormalizationServiceInterface::class);
        $termsExtractor = $this->createMock(CommercialTermsExtractorInterface::class);
        $riskService = $this->createMock(RiskAssessmentServiceInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $document = $this->createMock(QuotationDocumentInterface::class);
        $document->method('getTenantId')->willReturn('tenant-1');
        $document->method('getMetadata')->willReturn(['rfq_id' => 'rfq-1']);
        $repo->method('findById')->willReturn($document);

        $tenant = $this->createMock(OrchestratorTenantInterface::class);
        $tenant->method('getCurrency')->willReturn('USD');
        $tenantRepository->method('findById')->willReturn($tenant);
        $procurementManager->method('getRequisition')->willReturn(null);

        $coordinator = new QuotationIntelligenceCoordinator(
            $processor,
            $repo,
            $tenantRepository,
            $procurementManager,
            $mapper,
            $normService,
            $termsExtractor,
            $riskService,
            $logger
        );

        $this->expectException(MissingRfqContextException::class);
        $coordinator->processQuote('tenant-1', 'doc-123');
    }

    public function test_throws_when_requisition_has_no_lines_or_base_unit(): void
    {
        $processor = $this->createMock(OrchestratorContentProcessorInterface::class);
        $repo = $this->createMock(OrchestratorDocumentRepositoryInterface::class);
        $tenantRepository = $this->createMock(OrchestratorTenantRepositoryInterface::class);
        $procurementManager = $this->createMock(OrchestratorProcurementManagerInterface::class);
        $mapper = $this->createMock(SemanticMapperInterface::class);
        $normService = $this->createMock(QuoteNormalizationServiceInterface::class);
        $termsExtractor = $this->createMock(CommercialTermsExtractorInterface::class);
        $riskService = $this->createMock(RiskAssessmentServiceInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $document = $this->createMock(QuotationDocumentInterface::class);
        $document->method('getTenantId')->willReturn('tenant-1');
        $document->method('getMetadata')->willReturn(['rfq_id' => 'rfq-1']);
        $repo->method('findById')->willReturn($document);

        $tenant = $this->createMock(OrchestratorTenantInterface::class);
        $tenant->method('getCurrency')->willReturn('USD');
        $tenantRepository->method('findById')->willReturn($tenant);

        $requisition = $this->createMock(OrchestratorRequisitionInterface::class);
        $requisition->method('getLines')->willReturn([]);
        $procurementManager->method('getRequisition')->willReturn($requisition);

        $coordinator = new QuotationIntelligenceCoordinator(
            $processor,
            $repo,
            $tenantRepository,
            $procurementManager,
            $mapper,
            $normService,
            $termsExtractor,
            $riskService,
            $logger
        );

        $this->expectException(MissingRfqContextException::class);
        $coordinator->processQuote('tenant-1', 'doc-123');
    }

    public function test_throws_when_semantic_mapping_payload_is_invalid(): void
    {
        $processor = $this->createMock(OrchestratorContentProcessorInterface::class);
        $repo = $this->createMock(OrchestratorDocumentRepositoryInterface::class);
        $tenantRepository = $this->createMock(OrchestratorTenantRepositoryInterface::class);
        $procurementManager = $this->createMock(OrchestratorProcurementManagerInterface::class);
        $mapper = $this->createMock(SemanticMapperInterface::class);
        $normService = $this->createMock(QuoteNormalizationServiceInterface::class);
        $termsExtractor = $this->createMock(CommercialTermsExtractorInterface::class);
        $riskService = $this->createMock(RiskAssessmentServiceInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $document = $this->createMock(QuotationDocumentInterface::class);
        $document->method('getTenantId')->willReturn('tenant-1');
        $document->method('getMetadata')->willReturn(['rfq_id' => 'rfq-1']);
        $document->method('getStoragePath')->willReturn('/tmp/quote.pdf');
        $repo->method('findById')->willReturn($document);

        $tenant = $this->createMock(OrchestratorTenantInterface::class);
        $tenant->method('getCurrency')->willReturn('USD');
        $tenantRepository->method('findById')->willReturn($tenant);

        $line = $this->createMock(OrchestratorRequisitionLineInterface::class);
        $line->method('getUnit')->willReturn('EA');
        $requisition = $this->createMock(OrchestratorRequisitionInterface::class);
        $requisition->method('getLines')->willReturn([$line]);
        $procurementManager->method('getRequisition')->willReturn($requisition);

        $analysis = new ContentAnalysisResult(
            predictedType: DocumentType::PDF,
            confidenceScore: 0.95,
            extractedMetadata: [
                'lines' => [[
                    'rfq_line_id' => 'rfq-line-1',
                    'description' => 'Laptop',
                    'quantity' => 1,
                    'unit' => 'EA',
                    'unit_price' => 1000,
                ]],
            ],
            containsPII: false,
            suggestedTags: [],
            rawAnalysis: []
        );
        $processor->method('analyze')->willReturn($analysis);

        $mapper->method('mapToTaxonomy')->willReturn(['code' => 'bad']);
        $mapper->method('validateCode')->willReturn(false);

        $coordinator = new QuotationIntelligenceCoordinator(
            $processor,
            $repo,
            $tenantRepository,
            $procurementManager,
            $mapper,
            $normService,
            $termsExtractor,
            $riskService,
            $logger
        );

        $this->expectException(SemanticMappingException::class);
        $coordinator->processQuote('tenant-1', 'doc-123');
    }

    public function test_processes_with_empty_fx_lock_date_and_skips_invalid_lines(): void
    {
        $processor = $this->createMock(OrchestratorContentProcessorInterface::class);
        $repo = $this->createMock(OrchestratorDocumentRepositoryInterface::class);
        $tenantRepository = $this->createMock(OrchestratorTenantRepositoryInterface::class);
        $procurementManager = $this->createMock(OrchestratorProcurementManagerInterface::class);
        $mapper = $this->createMock(SemanticMapperInterface::class);
        $normService = $this->createMock(QuoteNormalizationServiceInterface::class);
        $termsExtractor = $this->createMock(CommercialTermsExtractorInterface::class);
        $riskService = $this->createMock(RiskAssessmentServiceInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $document = $this->createMock(QuotationDocumentInterface::class);
        $document->method('getTenantId')->willReturn('tenant-1');
        $document->method('getMetadata')->willReturn(['rfq_id' => 'rfq-1', 'fx_lock_date' => '']);
        $document->method('getStoragePath')->willReturn('/tmp/quote.pdf');
        $repo->method('findById')->willReturn($document);

        $tenant = $this->createMock(OrchestratorTenantInterface::class);
        $tenant->method('getCurrency')->willReturn('USD');
        $tenantRepository->method('findById')->willReturn($tenant);

        $line = $this->createMock(OrchestratorRequisitionLineInterface::class);
        $line->method('getUnit')->willReturn('EA');
        $requisition = $this->createMock(OrchestratorRequisitionInterface::class);
        $requisition->method('getLines')->willReturn([$line]);
        $procurementManager->method('getRequisition')->willReturn($requisition);

        $analysis = new ContentAnalysisResult(
            predictedType: DocumentType::PDF,
            confidenceScore: 0.95,
            extractedMetadata: [
                'lines' => [
                    'invalid-row',
                    ['description' => 'No quantity'],
                    [
                        'rfq_line_id' => 'rfq-line-1',
                        'description' => 'Valid Laptop',
                        'quantity' => 1,
                        'unit' => 'EA',
                        'unit_price' => 1000,
                    ],
                ],
            ],
            containsPII: false,
            suggestedTags: [],
            rawAnalysis: []
        );
        $processor->method('analyze')->willReturn($analysis);

        $mapper->method('mapToTaxonomy')->willReturn(['code' => '43211503', 'confidence' => 0.95, 'version' => '1.0.0']);
        $mapper->method('validateCode')->willReturn(true);
        $normService->method('normalizeQuantity')->willReturn(1.0);
        $normService->expects($this->once())
            ->method('normalizePrice')
            ->with(1000.0, 'USD', 'USD', null)
            ->willReturn(1000.0);
        $termsExtractor->method('extract')->willReturn([
            'incoterm' => null,
            'payment_days' => null,
            'lead_time_days' => null,
            'warranty_months' => null,
        ]);
        $riskService->method('assess')->willReturn([]);

        $coordinator = new QuotationIntelligenceCoordinator(
            $processor,
            $repo,
            $tenantRepository,
            $procurementManager,
            $mapper,
            $normService,
            $termsExtractor,
            $riskService,
            $logger
        );

        $result = $coordinator->processQuote('tenant-1', 'doc-123');

        $this->assertCount(1, $result['lines']);
        $this->assertSame('rfq-line-1', $result['lines'][0]['rfq_line_id']);
    }

    public function test_preserves_line_and_degrades_confidence_when_uom_normalization_fails(): void
    {
        $processor = $this->createMock(OrchestratorContentProcessorInterface::class);
        $repo = $this->createMock(OrchestratorDocumentRepositoryInterface::class);
        $tenantRepository = $this->createMock(OrchestratorTenantRepositoryInterface::class);
        $procurementManager = $this->createMock(OrchestratorProcurementManagerInterface::class);
        $mapper = $this->createMock(SemanticMapperInterface::class);
        $normService = $this->createMock(QuoteNormalizationServiceInterface::class);
        $termsExtractor = $this->createMock(CommercialTermsExtractorInterface::class);
        $riskService = $this->createMock(RiskAssessmentServiceInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $document = $this->createMock(QuotationDocumentInterface::class);
        $document->method('getTenantId')->willReturn('tenant-1');
        $document->method('getMetadata')->willReturn(['rfq_id' => 'rfq-1']);
        $document->method('getStoragePath')->willReturn('/tmp/quote.pdf');
        $repo->method('findById')->willReturn($document);

        $tenant = $this->createMock(OrchestratorTenantInterface::class);
        $tenant->method('getCurrency')->willReturn('USD');
        $tenantRepository->method('findById')->willReturn($tenant);

        $line = $this->createMock(OrchestratorRequisitionLineInterface::class);
        $line->method('getUnit')->willReturn('JOB');
        $requisition = $this->createMock(OrchestratorRequisitionInterface::class);
        $requisition->method('getLines')->willReturn([$line]);
        $procurementManager->method('getRequisition')->willReturn($requisition);

        $analysis = new ContentAnalysisResult(
            predictedType: DocumentType::PDF,
            confidenceScore: 0.95,
            extractedMetadata: [
                'lines' => [[
                    'rfq_line_id' => 'rfq-line-1',
                    'description' => 'Honeywell thermostat',
                    'quantity' => 1,
                    'unit' => 'EA',
                    'unit_price' => 185,
                ]],
            ],
            containsPII: false,
            suggestedTags: [],
            rawAnalysis: []
        );
        $processor->method('analyze')->willReturn($analysis);

        $mapper->method('mapToTaxonomy')->willReturn(['code' => '41112209', 'confidence' => 0.95, 'version' => '1.0.0']);
        $mapper->method('validateCode')->willReturn(true);
        $normService->method('normalizeQuantity')
            ->willThrowException(new UomNormalizationException('Cannot convert EA to JOB'));
        $normService->method('normalizePrice')->willReturn(185.0);
        $termsExtractor->method('extract')->willReturn([]);
        $riskService->method('assess')->willReturn([]);

        $coordinator = new QuotationIntelligenceCoordinator(
            $processor,
            $repo,
            $tenantRepository,
            $procurementManager,
            $mapper,
            $normService,
            $termsExtractor,
            $riskService,
            $logger
        );

        $result = $coordinator->processQuote('tenant-1', 'doc-123');

        $this->assertCount(1, $result['lines']);
        $this->assertSame(1.0, $result['lines'][0]['normalized_quantity']);
        $this->assertSame(0.6, $result['lines'][0]['ai_confidence']);
        $this->assertSame(
            'uom_conversion_failed',
            $result['lines'][0]['metadata']['normalization_warnings'][0]['code']
        );
    }
}
