<?php

declare(strict_types=1);

namespace App\Services\QuoteIntake\Contracts;

interface NormalizationSourceLineReadInterface
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getQuoteSubmissionId(): string;

    /**
     * Returns the RFQ line item identifier for this source line.
     *
     * @throws \DomainException When `rfq_line_item_id` is null on the underlying record.
     */
    public function getRfqLineItemId(): string;

    /**
     * @return array<string, mixed>
     */
    public function getRawData(): array;
}
