<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Exceptions;

/**
 * Exception for payment-related errors.
 */
class PaymentException extends ProcurementOperationsException
{
    /**
     * Create exception for payment not found.
     */
    public static function notFound(string $paymentId): self
    {
        return new self(
            sprintf('Payment not found: %s', $paymentId)
        );
    }

    /**
     * Create exception for payment batch not found.
     */
    public static function batchNotFound(string $batchId): self
    {
        return new self(
            sprintf('Payment batch not found: %s', $batchId)
        );
    }

    /**
     * Create exception for invalid payment status.
     */
    public static function invalidStatus(string $paymentId, string $currentStatus, string $requiredStatus): self
    {
        return new self(
            sprintf(
                'Payment %s has invalid status "%s", expected "%s"',
                $paymentId,
                $currentStatus,
                $requiredStatus
            )
        );
    }

    /**
     * Create exception for insufficient funds.
     */
    public static function insufficientFunds(
        string $bankAccountId,
        int $availableCents,
        int $requiredCents
    ): self {
        return new self(
            sprintf(
                'Insufficient funds in bank account %s. Available: %d cents, Required: %d cents',
                $bankAccountId,
                $availableCents,
                $requiredCents
            )
        );
    }

    /**
     * Create exception for invoice not matched.
     */
    public static function invoiceNotMatched(string $vendorBillId): self
    {
        return new self(
            sprintf('Invoice %s has not been matched and cannot be paid', $vendorBillId)
        );
    }

    /**
     * Create exception for invoice not approved.
     */
    public static function invoiceNotApproved(string $vendorBillId): self
    {
        return new self(
            sprintf('Invoice %s has not been approved for payment', $vendorBillId)
        );
    }

    /**
     * Create exception for payment already voided.
     */
    public static function alreadyVoided(string $paymentId): self
    {
        return new self(
            sprintf('Payment %s has already been voided', $paymentId)
        );
    }

    /**
     * Create exception for payment already executed.
     */
    public static function alreadyExecuted(string $paymentId): self
    {
        return new self(
            sprintf('Payment %s has already been executed', $paymentId)
        );
    }

    /**
     * Create exception for payment execution failure.
     */
    public static function executionFailed(string $paymentId, string $reason): self
    {
        return new self(
            sprintf('Payment %s execution failed: %s', $paymentId, $reason)
        );
    }

    /**
     * Create exception for bank account not found.
     */
    public static function bankAccountNotFound(string $bankAccountId): self
    {
        return new self(
            sprintf('Bank account not found: %s', $bankAccountId)
        );
    }

    /**
     * Create exception for invalid payment method.
     */
    public static function invalidPaymentMethod(string $method): self
    {
        return new self(
            sprintf('Invalid payment method: %s', $method)
        );
    }

    /**
     * Create exception for validation failure.
     */
    public static function validationFailed(string $ruleName, string $message): self
    {
        return new self(
            sprintf('Payment validation failed [%s]: %s', $ruleName, $message)
        );
    }
}
