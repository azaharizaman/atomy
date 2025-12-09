<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events;

use Nexus\Common\ValueObjects\Money;

/**
 * Event published when a credit memo is applied to an invoice.
 */
final readonly class MemoAppliedEvent
{
    public function __construct(
        public string $tenantId,
        public string $memoId,
        public string $memoNumber,
        public string $invoiceId,
        public string $invoiceNumber,
        public Money $appliedAmount,
        public Money $remainingMemoAmount,
        public Money $remainingInvoiceAmount,
        public string $appliedBy,
        public \DateTimeImmutable $appliedAt,
    ) {}

    /**
     * Check if memo is fully applied.
     */
    public function isMemoFullyApplied(): bool
    {
        return $this->remainingMemoAmount->isZero();
    }

    /**
     * Check if invoice is fully paid.
     */
    public function isInvoiceFullyPaid(): bool
    {
        return $this->remainingInvoiceAmount->isZero();
    }
}
