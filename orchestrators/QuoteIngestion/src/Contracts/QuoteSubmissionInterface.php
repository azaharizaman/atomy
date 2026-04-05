<?php

declare(strict_types=1);

namespace Nexus\QuoteIngestion\Contracts;

/**
 * Interface QuoteSubmissionInterface
 *
 * Defines the contract for a quote submission from a vendor.
 */
interface QuoteSubmissionInterface
{
    /**
     * Get the unique identifier of the submission.
     */
    public function getId(): string;

    /**
     * Get the tenant ID associated with the submission.
     */
    public function getTenantId(): string;

    /**
     * Get the RFQ ID associated with the submission.
     */
    public function getRfqId(): string;

    /**
     * Get the name of the vendor who submitted the quote.
     */
    public function getVendorName(): string;
}
