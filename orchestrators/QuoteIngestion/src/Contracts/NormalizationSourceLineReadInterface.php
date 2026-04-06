<?php

declare(strict_types=1);

namespace Nexus\QuoteIngestion\Contracts;

/**
 * Interface NormalizationSourceLineReadInterface
 *
 * Defines the contract for reading a source line.
 */
interface NormalizationSourceLineReadInterface
{
    /**
     * Get the unique identifier of the source line.
     */
    public function getId(): string;

    /**
     * Get the tenant ID associated with the line.
     */
    public function getTenantId(): string;

    /**
     * Get the quote submission ID associated with the line.
     */
    public function getQuoteSubmissionId(): string;

    /**
     * Get the RFQ line item ID associated with the line.
     */
    public function getRfqLineItemId(): string;

    /**
     * Get the raw data associated with the line.
     *
     * @return array<string, mixed>
     */
    public function getRawData(): array;
}
