<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Contracts;

/**
 * Builds an apples-to-apples comparison matrix across multiple vendor quotes.
 */
interface QuoteComparisonMatrixServiceInterface
{
    /**
     * @param string $tenantId
     * @param string $rfqId
     * @param array<int, array{
     *   vendor_id: string,
     *   lines: array<\Nexus\QuotationIntelligence\DTOs\NormalizedQuoteLine>
     * }> $vendorLineSets
     *
     * @return array{
     *   tenant_id: string,
     *   rfq_id: string,
     *   clusters: array<int, array<string, mixed>>
     * }
     */
    public function buildMatrix(string $tenantId, string $rfqId, array $vendorLineSets): array;
}

