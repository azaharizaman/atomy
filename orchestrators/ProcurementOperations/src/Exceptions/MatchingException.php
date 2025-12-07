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
}
