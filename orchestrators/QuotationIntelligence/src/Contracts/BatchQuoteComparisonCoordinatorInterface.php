<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Contracts;

/**
 * Coordinates multi-document quotation comparison for a single RFQ.
 */
interface BatchQuoteComparisonCoordinatorInterface
{
    /**
     * @param string $tenantId
     * @param string $rfqId
     * @param array<int, string> $documentIds
     *
     * @return array{
     *   tenant_id: string,
     *   rfq_id: string,
     *   documents_processed: int,
     *   matrix: array<string, mixed>,
     *   scoring: array<string, mixed>,
     *   approval: array<string, mixed>,
     *   decision_trail: array<int, array<string, mixed>>,
     *   vendors: array<int, array{
     *     vendor_id: string,
     *     line_count: int,
     *     risks: array<int, array<string, mixed>>
     *   }>
     * }
     */
    public function compareQuotes(string $tenantId, string $rfqId, array $documentIds): array;
}
