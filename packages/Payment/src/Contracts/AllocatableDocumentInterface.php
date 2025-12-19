<?php

declare(strict_types=1);

namespace Nexus\Payment\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Enums\AllocationMethod;
use Nexus\Payment\ValueObjects\AllocationResult;

/**
 * Contract for allocatable documents.
 *
 * Represents documents that can receive payment allocation
 * (e.g., invoices, bills, orders).
 */
interface AllocatableDocumentInterface
{
    /**
     * Get the document ID.
     */
    public function getId(): string;

    /**
     * Get the document type (e.g., 'invoice', 'bill', 'order').
     */
    public function getDocumentType(): string;

    /**
     * Get the total amount due on this document.
     */
    public function getAmountDue(): Money;

    /**
     * Get the outstanding balance (total - already paid).
     */
    public function getOutstandingBalance(): Money;

    /**
     * Get the document date for sorting purposes.
     */
    public function getDocumentDate(): \DateTimeImmutable;

    /**
     * Get the due date for sorting purposes.
     */
    public function getDueDate(): ?\DateTimeImmutable;

    /**
     * Check if this document is fully paid.
     */
    public function isFullyPaid(): bool;

    /**
     * Check if this document is partially paid.
     */
    public function isPartiallyPaid(): bool;

    /**
     * Get the currency code.
     */
    public function getCurrency(): string;
}
