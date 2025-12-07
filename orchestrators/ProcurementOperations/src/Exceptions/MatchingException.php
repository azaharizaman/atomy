<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Exceptions;

/**
 * Exception for invoice matching-related errors.
 */
class MatchingException extends ProcurementOperationsException
{
    /**
     * Create exception for invoice already matched.
     */
    public static function alreadyMatched(string $vendorBillId): self
    {
        return new self(
            sprintf('Vendor bill %s has already been matched', $vendorBillId)
        );
    }

    /**
     * Create exception for vendor mismatch.
     */
    public static function vendorMismatch(
        string $vendorBillId,
        string $invoiceVendorId,
        string $purchaseOrderId,
        string $poVendorId
    ): self {
        return new self(
            sprintf(
                'Vendor mismatch: Invoice %s is from vendor %s, but PO %s is for vendor %s',
                $vendorBillId,
                $invoiceVendorId,
                $purchaseOrderId,
                $poVendorId
            )
        );
    }

    /**
     * Create exception for currency mismatch.
     */
    public static function currencyMismatch(
        string $vendorBillId,
        string $invoiceCurrency,
        string $purchaseOrderId,
        string $poCurrency
    ): self {
        return new self(
            sprintf(
                'Currency mismatch: Invoice %s is in %s, but PO %s is in %s',
                $vendorBillId,
                $invoiceCurrency,
                $purchaseOrderId,
                $poCurrency
            )
        );
    }

    /**
     * Create exception for no goods receipts to match.
     */
    public static function noGoodsReceipts(string $vendorBillId, string $purchaseOrderId): self
    {
        return new self(
            sprintf(
                'Cannot match invoice %s: No goods receipts found for PO %s',
                $vendorBillId,
                $purchaseOrderId
            )
        );
    }

    /**
     * Create exception for invoice not found.
     */
    public static function invoiceNotFound(string $vendorBillId): self
    {
        return new self(
            sprintf('Vendor bill not found: %s', $vendorBillId)
        );
    }

    /**
     * Create exception for validation failures.
     *
     * @param array<string, array{message: string, data: array<string, mixed>}> $failures
     */
    public static function validationFailed(string $vendorBillId, array $failures): self
    {
        $ruleNames = array_keys($failures);
        return new self(
            sprintf(
                'Invoice matching validation failed for %s. Failed rules: %s',
                $vendorBillId,
                implode(', ', $ruleNames)
            )
        );
    }

    /**
     * Create exception for variance exceeding tolerance.
     */
    public static function varianceExceedsTolerance(
        string $vendorBillId,
        float $priceVariance,
        float $quantityVariance,
        float $priceTolerance,
        float $quantityTolerance
    ): self {
        return new self(
            sprintf(
                'Variance exceeds tolerance for invoice %s. Price: %.2f%% (max %.2f%%), Qty: %.2f%% (max %.2f%%)',
                $vendorBillId,
                $priceVariance,
                $priceTolerance,
                $quantityVariance,
                $quantityTolerance
            )
        );
    }

    /**
     * Create exception for unauthorized variance approval.
     */
    public static function unauthorizedApproval(string $userId, string $vendorBillId): self
    {
        return new self(
            sprintf(
                'User %s is not authorized to approve variance for invoice %s',
                $userId,
                $vendorBillId
            )
        );
    }
}
