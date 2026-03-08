<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Contracts;

/**
 * Validates whether an RFQ and its quotes are eligible for a comparison run.
 *
 * Enforces business rules such as:
 * - RFQ closing date must have passed before a "final" run.
 * - All quotes must have completed normalization.
 * - Outstanding human oversight flags must be resolved for final runs.
 */
interface ComparisonReadinessValidatorInterface
{
    /**
     * Validate that a comparison run may proceed.
     *
     * @param string $tenantId
     * @param string $rfqId
     * @param array<int, array{vendor_id: string, lines: array}> $vendorLineSets
     * @param bool $isPreview  True for preview/draft runs (relaxed rules).
     *
     * @return ComparisonReadinessResultInterface
     */
    public function validate(
        string $tenantId,
        string $rfqId,
        array $vendorLineSets,
        bool $isPreview = false
    ): ComparisonReadinessResultInterface;
}
