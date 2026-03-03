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
use Nexus\Tenant\Contracts\TenantRepositoryInterface;
use Nexus\Procurement\Contracts\ProcurementManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Main coordinator orchestrating the QuotationIntelligence pipeline.
 */
final readonly class QuotationIntelligenceCoordinator implements QuotationIntelligenceCoordinatorInterface
{
    public function __construct(
        private ContentProcessorInterface $documentProcessor,
        private DocumentRepositoryInterface $documentRepository,
        private TenantRepositoryInterface $tenantRepository,
        private ProcurementManagerInterface $procurementManager,
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

        // 1. Fetch document metadata and enforce tenant ownership
        $document = $this->documentRepository->findById($documentId);
        if (!$document || $document->getTenantId() !== $tenantId) {
            throw new \InvalidArgumentException("Document {$documentId} not found or access denied");
        }

        // 2. Fetch tenant settings for base currency
        $tenant = $this->tenantRepository->findById($tenantId);
        $baseCurrency = $tenant?->getCurrency() ?: 'USD';

        // 3. Fetch RFQ context for base unit
        $rfqId = $document->getMetadata()['rfq_id'] ?? 'unknown';
        $baseUnit = 'UNIT';
        if ($rfqId !== 'unknown') {
            try {
                $requisition = $this->procurementManager->getRequisition($rfqId);
                // Assuming first line unit as base unit if not explicitly defined on requisition
                $baseUnit = $requisition->getLines()[0]->getUnit() ?? 'UNIT';
            } catch (\Throwable) {
                // Fallback to default unit
            }
        }

        // 4. Extract structured data using Nexus\Document ML capabilities
        $analysis = $this->documentProcessor->analyze($document->getStoragePath());
        $extractedLines = $analysis->getExtractedField('lines', []);

        $normalizedLines = [];

        // 5. Loop through extracted lines and apply intelligence
        foreach ($extractedLines as $line) {
            // Validation: Ensure required keys exist and have valid values
            if (!isset($line['description']) || empty($line['description'])) {
                $this->logger->warning('Skipping quote line missing description', ['line' => $line]);
                continue;
            }

            if (!isset($line['quantity']) || !is_numeric($line['quantity'])) {
                $this->logger->warning('Skipping quote line with invalid quantity', ['line' => $line]);
                continue;
            }

            if (!isset($line['unit_price']) || !is_numeric($line['unit_price'])) {
                $this->logger->warning('Skipping quote line with invalid unit price', ['line' => $line]);
                continue;
            }

            if (!isset($line['rfq_line_id']) || empty($line['rfq_line_id'])) {
                $this->logger->warning('Skipping quote line missing rfq_line_id', ['line' => $line]);
                continue;
            }

            // A. Semantic Mapping (Taxonomy) with safe defaults
            $mapping = ['code' => 'unknown', 'confidence' => 0.0];
            try {
                $mappingResult = $this->semanticMapper->mapToTaxonomy((string)$line['description'], $tenantId);
                $mapping['code'] = $mappingResult['code'] ?? 'unknown';
                $mapping['confidence'] = $mappingResult['confidence'] ?? 0.0;
            } catch (\Throwable $e) {
                $this->logger->error('Semantic mapping failed for line', ['description' => $line['description'], 'error' => $e->getMessage()]);
            }

            // B. Normalization (UoM) with error handling
            $normQty = (float)$line['quantity'];
            $quotedUnit = $line['unit'] ?? 'UNIT';
            try {
                $normQty = $this->normalizationService->normalizeQuantity(
                    (float)$line['quantity'],
                    (string)$quotedUnit,
                    $baseUnit
                );
            } catch (\Throwable $e) {
                $this->logger->warning('UoM normalization failed for line', ['from' => $quotedUnit, 'to' => $baseUnit, 'error' => $e->getMessage()]);
            }

            // C. Normalization (Currency) with error handling
            $normPrice = (float)$line['unit_price'];
            $quotedCurrency = $line['currency'] ?? $baseCurrency;
            try {
                $normPrice = $this->normalizationService->normalizePrice(
                    (float)$line['unit_price'],
                    (string)$quotedCurrency,
                    $baseCurrency
                );
            } catch (\Throwable $e) {
                $this->logger->warning('Currency normalization failed for line', ['from' => $quotedCurrency, 'to' => $baseCurrency, 'error' => $e->getMessage()]);
            }

            // D. Build evidence snippets
            $evidence = new ExtractionEvidence(
                documentId: $documentId,
                page: 1, // Default to page 1 if not provided in extracted line
                bbox: $line['bbox'] ?? ['x' => 0, 'y' => 0, 'w' => 0, 'h' => 0],
                rawText: (string)$line['description']
            );
            $snippet = new QuoteSnippet('description', $evidence);

            $normalizedLines[] = new NormalizedQuoteLine(
                rfqLineId: (string)$line['rfq_line_id'],
                vendorDescription: (string)$line['description'],
                taxonomyCode: (string)$mapping['code'],
                quotedQuantity: (float)$line['quantity'],
                quotedUnit: (string)$quotedUnit,
                normalizedQuantity: (float)$normQty,
                quotedUnitPrice: (float)$line['unit_price'],
                normalizedUnitPrice: (float)$normPrice,
                aiConfidence: (float)$mapping['confidence'],
                snippets: [$snippet]
            );
        }

        // 6. Run Risk Assessment
        $risks = $this->riskAssessmentService->assess($tenantId, $rfqId, $normalizedLines);

        $this->logger->info('Quotation intelligence pipeline complete', [
            'document_id' => $documentId,
            'rfq_id' => $rfqId,
            'line_count' => count($normalizedLines),
            'risk_count' => count($risks),
        ]);

        return [
            'lines' => array_map(fn(NormalizedQuoteLine $l) => $l->toArray(), $normalizedLines),
            'risks' => $risks,
        ];
    }
}
