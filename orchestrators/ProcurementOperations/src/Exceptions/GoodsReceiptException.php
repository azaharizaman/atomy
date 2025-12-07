<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Exceptions;

/**
 * Exception for goods receipt-related errors.
 */
class GoodsReceiptException extends ProcurementOperationsException
{
    /**
     * Create exception for GR not found.
     */
    public static function notFound(string $goodsReceiptId): self
    {
        return new self(
            sprintf('Goods receipt not found: %s', $goodsReceiptId)
        );
    }

    /**
     * Create exception for invalid GR status.
     */
    public static function invalidStatus(string $goodsReceiptId, string $currentStatus, string $requiredStatus): self
    {
        return new self(
            sprintf(
                'Goods receipt %s has invalid status "%s", expected "%s"',
                $goodsReceiptId,
                $currentStatus,
                $requiredStatus
            )
        );
    }

    /**
     * Create exception for quantity exceeds ordered.
     */
    public static function quantityExceedsOrdered(
        string $lineId,
        float $orderedQuantity,
        float $alreadyReceived,
        float $attemptedReceive
    ): self {
        return new self(
            sprintf(
                'Line %s: Cannot receive %.2f units. Ordered: %.2f, Already received: %.2f, Remaining: %.2f',
                $lineId,
                $attemptedReceive,
                $orderedQuantity,
                $alreadyReceived,
                $orderedQuantity - $alreadyReceived
            )
        );
    }

    /**
     * Create exception for PO not open for receipts.
     */
    public static function purchaseOrderNotOpen(string $purchaseOrderId, string $status): self
    {
        return new self(
            sprintf(
                'Purchase order %s is not open for receipts (status: %s)',
                $purchaseOrderId,
                $status
            )
        );
    }

    /**
     * Create exception for GR already reversed.
     */
    public static function alreadyReversed(string $goodsReceiptId): self
    {
        return new self(
            sprintf('Goods receipt %s has already been reversed', $goodsReceiptId)
        );
    }

    /**
     * Create exception for GR has matched invoice.
     */
    public static function hasMatchedInvoice(string $goodsReceiptId, string $invoiceId): self
    {
        return new self(
            sprintf(
                'Goods receipt %s cannot be reversed because it is matched to invoice %s',
                $goodsReceiptId,
                $invoiceId
            )
        );
    }

    /**
     * Create exception for inventory update failure.
     */
    public static function inventoryUpdateFailed(string $goodsReceiptId, string $reason): self
    {
        return new self(
            sprintf('Failed to update inventory for goods receipt %s: %s', $goodsReceiptId, $reason)
        );
    }

    /**
     * Create exception for validation failure.
     *
     * @param array<string, string> $errors
     */
    public static function validationFailed(array $errors): self
    {
        return new self(
            'Goods receipt validation failed: ' . json_encode($errors)
        );
    }
}
