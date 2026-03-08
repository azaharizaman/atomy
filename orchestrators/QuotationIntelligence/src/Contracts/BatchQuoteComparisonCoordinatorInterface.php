<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Contracts;

/**
 * Coordinates multi-document quotation comparison for a single RFQ.
 */
interface BatchQuoteComparisonCoordinatorInterface
{
    /**
     * Execute a full (final) comparison run with all validation gates.
     *
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
     *   }>,
     *   is_preview: bool,
     *   readiness: array<string, mixed>
     * }
     */
    public function compareQuotes(string $tenantId, string $rfqId, array $documentIds): array;

    /**
     * Execute a preview (draft) comparison with relaxed validation.
     *
     * Decision trail is omitted for previews. Readiness warnings are included
     * in the response so the UI can surface what must be resolved before a
     * final run can be saved.
     *
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
     *   vendors: array<int, array{
     *     vendor_id: string,
     *     line_count: int,
     *     risks: array<int, array<string, mixed>>
     *   }>,
     *   is_preview: bool,
     *   readiness: array<string, mixed>
     * }
     */
    public function previewQuotes(string $tenantId, string $rfqId, array $documentIds): array;
}
