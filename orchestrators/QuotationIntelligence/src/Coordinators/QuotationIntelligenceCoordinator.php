<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Coordinators;

use Nexus\QuotationIntelligence\Contracts\QuotationIntelligenceCoordinatorInterface;
use Nexus\QuotationIntelligence\Contracts\SemanticMapperInterface;
use Nexus\QuotationIntelligence\Contracts\QuoteNormalizationServiceInterface;
use Nexus\QuotationIntelligence\Contracts\RiskAssessmentServiceInterface;
use Nexus\QuotationIntelligence\DTOs\NormalizedQuoteLine;
use Nexus\QuotationIntelligence\ValueObjects\ExtractionEvidence;
use Nexus\QuotationIntelligence\ValueObjects\QuoteSnippet;
use Nexus\Document\Contracts\ContentProcessorInterface;
use Nexus\Document\Contracts\DocumentRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Main coordinator orchestrating the QuotationIntelligence pipeline.
 */
final readonly class QuotationIntelligenceCoordinator implements QuotationIntelligenceCoordinatorInterface
{
    public function __construct(
        private ContentProcessorInterface $documentProcessor,
        private DocumentRepositoryInterface $documentRepository,
        private SemanticMapperInterface $semanticMapper,
        private QuoteNormalizationServiceInterface $normalizationService,
        private RiskAssessmentServiceInterface $riskAssessmentService,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function processQuote(string $tenantId, string $documentId): array
    {
        $this->logger->info('Starting full quotation intelligence pipeline', [
            'tenant_id' => $tenantId,
            'document_id' => $documentId,
        ]);

        // 1. Fetch document metadata
        $document = $this->documentRepository->findById($documentId);
        if (!$document) {
            throw new \InvalidArgumentException("Document {$documentId} not found");
        }

        // 2. Extract structured data using Nexus\Document ML capabilities
        // Note: We use a simplified return from analyze() for this orchestrator
        $analysis = $this->documentProcessor->analyze($document->getStoragePath());
        $extractedLines = $analysis->getExtractedField('lines', []);

        $normalizedLines = [];
        $baseCurrency = 'USD'; // Assuming tenant base currency, in real app fetch from tenant settings
        $baseUnit = 'UNIT'; // From RFQ context

        // 3. Loop through extracted lines and apply intelligence
        foreach ($extractedLines as $line) {
            // A. Semantic Mapping (Taxonomy)
            $mapping = $this->semanticMapper->mapToTaxonomy($line['description'], $tenantId);

            // B. Normalization (UoM)
            $normQty = $this->normalizationService->normalizeQuantity(
                (float)$line['quantity'],
                $line['unit'] ?? 'UNIT',
                $baseUnit
            );

            // C. Normalization (Currency)
            $normPrice = $this->normalizationService->normalizePrice(
                (float)$line['unit_price'],
                $line['currency'] ?? 'USD',
                $baseCurrency
            );

            // D. Build evidence snippets (Mocking coordinates for this implementation)
            $evidence = new ExtractionEvidence(
                documentId: $documentId,
                page: 1,
                bbox: ['x' => 10, 'y' => 20, 'w' => 50, 'h' => 5],
                rawText: $line['description']
            );
            $snippet = new QuoteSnippet('description', $evidence);

            $normalizedLines[] = new NormalizedQuoteLine(
                rfqLineId: $line['rfq_line_id'] ?? 'unknown',
                vendorDescription: $line['description'],
                taxonomyCode: $mapping['code'],
                quotedQuantity: (float)$line['quantity'],
                quotedUnit: $line['unit'] ?? 'UNIT',
                normalizedQuantity: $normQty,
                quotedUnitPrice: (float)$line['unit_price'],
                normalizedUnitPrice: $normPrice,
                aiConfidence: $mapping['confidence'],
                snippets: [$snippet]
            );
        }

        // 4. Run Risk Assessment
        $risks = $this->riskAssessmentService->assess($tenantId, 'rfq-context', $normalizedLines);

        $this->logger->info('Quotation intelligence pipeline complete', [
            'document_id' => $documentId,
            'line_count' => count($normalizedLines),
            'risk_count' => count($risks),
        ]);

        return [
            'lines' => array_map(fn(NormalizedQuoteLine $l) => $l->toArray(), $normalizedLines),
            'risks' => $risks,
        ];
    }
}
