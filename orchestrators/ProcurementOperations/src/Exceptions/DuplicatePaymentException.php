<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Exceptions;

/**
 * Exception for duplicate payment attempts.
 */
class DuplicatePaymentException extends PaymentException
{
    /**
     * Create exception for duplicate payment to invoice.
     */
    public static function invoiceAlreadyPaid(string $vendorBillId, string $existingPaymentId): self
    {
        return new self(
            sprintf(
                'Invoice %s has already been paid (Payment ID: %s)',
                $vendorBillId,
                $existingPaymentId
            )
        );
    }

    /**
     * Create exception for duplicate payment reference.
     */
    public static function duplicateReference(string $paymentReference): self
    {
        return new self(
            sprintf('Duplicate payment reference: %s', $paymentReference)
        );
    }

    /**
     * Create exception for invoice in existing payment batch.
     */
    public static function invoiceInPendingBatch(string $vendorBillId, string $batchId): self
    {
        return new self(
            sprintf(
                'Invoice %s is already in pending payment batch: %s',
                $vendorBillId,
                $batchId
            )
        );
    }
}
