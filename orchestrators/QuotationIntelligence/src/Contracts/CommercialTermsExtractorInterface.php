<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Contracts;

/**
 * Extracts and normalizes commercial terms from quote text.
 */
interface CommercialTermsExtractorInterface
{
    /**
     * @param string $text
     *
     * @return array{
     *   incoterm: string|null,
     *   payment_days: int|null,
     *   lead_time_days: int|null,
     *   warranty_months: int|null
     * }
     */
    public function extract(string $text): array;
}

