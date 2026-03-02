<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Contracts;

/**
 * Main coordinator for the quotation intelligence workflow.
 */
interface QuotationIntelligenceCoordinatorInterface
{
    /**
     * Orchestrate the full intelligence flow for a vendor quote.
     * 
     * Pipeline:
     * 1. Extract raw data from Document (Document package + ML)
     * 2. Semantic Mapping (Taxonomy)
     * 3. Normalization (UoM/Currency)
     * 4. Risk Assessment
     * 
     * @param string $tenantId
     * @param string $documentId The source quote document
     * @return array{lines: array, risks: array} The final intelligence results
     */
    public function processQuote(string $tenantId, string $documentId): array;
}
