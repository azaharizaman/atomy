<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Coordinators;

use Nexus\QuotationIntelligence\Contracts\QuotationIntelligenceCoordinatorInterface;
use Nexus\QuotationIntelligence\Contracts\SemanticMapperInterface;
use Nexus\QuotationIntelligence\Contracts\QuoteNormalizationServiceInterface;
use Nexus\QuotationIntelligence\Contracts\RiskAssessmentServiceInterface;
use Nexus\QuotationIntelligence\Contracts\CommercialTermsExtractorInterface;
use Nexus\QuotationIntelligence\Contracts\OrchestratorContentProcessorInterface;
use Nexus\QuotationIntelligence\Contracts\OrchestratorDocumentRepositoryInterface;
use Nexus\QuotationIntelligence\Contracts\OrchestratorTenantRepositoryInterface;
use Nexus\QuotationIntelligence\Contracts\OrchestratorProcurementManagerInterface;
use Nexus\QuotationIntelligence\Exceptions\DocumentAccessDeniedException;
use Nexus\QuotationIntelligence\Exceptions\InvalidNormalizationContextException;
use Nexus\QuotationIntelligence\Exceptions\MissingRfqContextException;
use Nexus\QuotationIntelligence\Exceptions\TenantContextNotFoundException;
use Nexus\QuotationIntelligence\Exceptions\SemanticMappingException;
use Nexus\QuotationIntelligence\DTOs\NormalizedQuoteLine;
use Nexus\QuotationIntelligence\ValueObjects\NormalizationContext;
use Nexus\QuotationIntelligence\ValueObjects\ExtractionEvidence;
use Nexus\QuotationIntelligence\ValueObjects\QuoteSnippet;
use Psr\Log\LoggerInterface;

/**
 * Main coordinator orchestrating the QuotationIntelligence pipeline.
 */
final readonly class QuotationIntelligenceCoordinator implements QuotationIntelligenceCoordinatorInterface
{
    public function __construct(
        private OrchestratorContentProcessorInterface $documentProcessor,
        private OrchestratorDocumentRepositoryInterface $documentRepository,
        private OrchestratorTenantRepositoryInterface $tenantRepository,
        private OrchestratorProcurementManagerInterface $procurementManager,
        private SemanticMapperInterface $semanticMapper,
        private QuoteNormalizationServiceInterface $normalizationService,
        private CommercialTermsExtractorInterface $commercialTermsExtractor,
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
        if ($document === null || $document->getTenantId() !== $tenantId) {
            throw new DocumentAccessDeniedException("Document {$documentId} not found or access denied");
        }

        // 2. Fetch tenant settings for base currency
        $tenant = $this->tenantRepository->findById($tenantId);
        if ($tenant === null) {
            throw new TenantContextNotFoundException(sprintf('Tenant context not found for "%s"', $tenantId));
        }
        $baseCurrency = $tenant->getCurrency();

        // 3. Fetch RFQ context for base unit
        $rfqId = (string)($document->getMetadata()['rfq_id'] ?? '');
        if ($rfqId === '') {
            throw new MissingRfqContextException("Missing required rfq_id metadata in document {$documentId}");
        }
        $requisition = $this->procurementManager->getRequisition($rfqId);
        if ($requisition === null) {
            throw new MissingRfqContextException("Unable to resolve requisition context for rfq_id {$rfqId}");
        }
        $lines = $requisition->getLines();
        if ($lines === [] || !isset($lines[0])) {
            throw new MissingRfqContextException("Requisition {$rfqId} has no line context for base unit");
        }
        $baseUnit = (string)($lines[0]->getUnit() ?? '');
        if ($baseUnit === '') {
            throw new MissingRfqContextException("Requisition {$rfqId} base unit is empty");
        }

        $normalizationContext = new NormalizationContext(
            baseUnit: $baseUnit,
            baseCurrency: $baseCurrency,
            fxLockDate: $this->resolveFxLockDate($document->getMetadata(), $documentId)
        );

        // 4. Extract structured data using Nexus\Document ML capabilities
        $analysis = $this->documentProcessor->analyze($document->getStoragePath());
        $extractedLines = $analysis->getExtractedField('lines', []);
        $normalizedLines = [];

        // 5. Loop through extracted lines and apply intelligence
        foreach ($extractedLines as $line) {
            // Guard against non-array extracted items before using offset access
            if (!is_array($line)) {
                $this->logger->warning('Skipping non-array quote line item', ['type' => gettype($line)]);
                continue;
            }

            // Validation: Ensure required keys exist and have valid values
            if (!isset($line['description']) || empty($line['description'])) {
                $this->logger->warning('Skipping quote line missing description', [
                    'rfq_line_id' => $line['rfq_line_id'] ?? null,
                    'has_description' => false
                ]);
                continue;
            }

            if (!isset($line['quantity']) || !is_numeric($line['quantity'])) {
                $this->logger->warning('Skipping quote line with invalid quantity', [
                    'rfq_line_id' => $line['rfq_line_id'] ?? null,
                    'quantity_present' => isset($line['quantity']),
                    'quantity' => isset($line['quantity']) ? (float)$line['quantity'] : null
                ]);
                continue;
            }

            if (!isset($line['unit_price']) || !is_numeric($line['unit_price'])) {
                $this->logger->warning('Skipping quote line with invalid unit price', [
                    'rfq_line_id' => $line['rfq_line_id'] ?? null,
                    'unit_price_present' => isset($line['unit_price'])
                ]);
                continue;
            }

            if (!isset($line['rfq_line_id']) || empty($line['rfq_line_id'])) {
                $this->logger->warning('Skipping quote line missing rfq_line_id', [
                    'has_rfq_line_id' => false
                ]);
                continue;
            }

            // A. Semantic Mapping (Taxonomy) with strict validation
            $mapping = $this->semanticMapper->mapToTaxonomy((string)$line['description'], $tenantId);
            if (
                !isset($mapping['code'], $mapping['confidence'], $mapping['version'])
                || !$this->semanticMapper->validateCode((string)$mapping['code'], (string)$mapping['version'])
            ) {
                throw new SemanticMappingException(
                    sprintf('Invalid taxonomy mapping payload for RFQ line "%s"', (string)$line['rfq_line_id'])
                );
            }

            // B. Normalization (UoM)
            $quotedUnit = $line['unit'] ?? 'UNIT';
            $normQty = $this->normalizationService->normalizeQuantity(
                (float)$line['quantity'],
                (string)$quotedUnit,
                $baseUnit
            );

            // C. Normalization (Currency)
            $quotedCurrency = $line['currency'] ?? $baseCurrency;
            $normPrice = $this->normalizationService->normalizePrice(
                (float)$line['unit_price'],
                (string)$quotedCurrency,
                $baseCurrency,
                $normalizationContext->fxLockDate
            );

            $commercialTermsText = trim(
                sprintf('%s %s', (string)($line['terms'] ?? ''), (string)$line['description'])
            );
            $commercialTerms = $this->commercialTermsExtractor->extract($commercialTermsText);

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
                snippets: [$snippet],
                metadata: [
                    'vendor_id' => (string)($document->getMetadata()['vendor_id'] ?? ''),
                    'mapping_version' => (string)$mapping['version'],
                    'normalization_context' => $normalizationContext->toArray(),
                    'commercial_terms' => $commercialTerms,
                ]
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

    /**
     * @param array<string, mixed> $metadata
     */
    private function resolveFxLockDate(array $metadata, string $documentId): ?\DateTimeImmutable
    {
        $rawDate = (string)($metadata['fx_lock_date'] ?? '');
        if ($rawDate === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $rawDate);
        $errors = \DateTimeImmutable::getLastErrors();
        $hasErrors = $errors !== false && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0);

        if ($date === false || $hasErrors || $date->format('Y-m-d') !== $rawDate) {
            throw new InvalidNormalizationContextException(
                sprintf('Invalid fx_lock_date "%s" in document %s metadata', $rawDate, $documentId)
            );
        }

        return $date;
    }
}
