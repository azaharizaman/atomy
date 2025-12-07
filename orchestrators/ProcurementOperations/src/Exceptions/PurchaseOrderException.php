<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Exceptions;

/**
 * Exception for purchase order-related errors.
 */
class PurchaseOrderException extends ProcurementOperationsException
{
    /**
     * Create exception for PO not found.
     */
    public static function notFound(string $purchaseOrderId): self
    {
        return new self(
            sprintf('Purchase order not found: %s', $purchaseOrderId)
        );
    }

    /**
     * Create exception for invalid PO status.
     */
    public static function invalidStatus(string $purchaseOrderId, string $currentStatus, string $requiredStatus): self
    {
        return new self(
            sprintf(
                'Purchase order %s has invalid status "%s", expected "%s"',
                $purchaseOrderId,
                $currentStatus,
                $requiredStatus
            )
        );
    }

    /**
     * Create exception for PO already sent.
     */
    public static function alreadySent(string $purchaseOrderId): self
    {
        return new self(
            sprintf('Purchase order %s has already been sent to vendor', $purchaseOrderId)
        );
    }

    /**
     * Create exception for PO already closed.
     */
    public static function alreadyClosed(string $purchaseOrderId): self
    {
        return new self(
            sprintf('Purchase order %s is already closed', $purchaseOrderId)
        );
    }

    /**
     * Create exception for PO with receipts.
     */
    public static function hasGoodsReceipts(string $purchaseOrderId): self
    {
        return new self(
            sprintf(
                'Purchase order %s cannot be cancelled because goods have been received',
                $purchaseOrderId
            )
        );
    }

    /**
     * Create exception for inactive vendor.
     */
    public static function inactiveVendor(string $vendorId): self
    {
        return new self(
            sprintf('Cannot create PO for inactive vendor: %s', $vendorId)
        );
    }

    /**
     * Create exception for amendment not allowed.
     */
    public static function amendmentNotAllowed(string $purchaseOrderId, string $reason): self
    {
        return new self(
            sprintf(
                'Purchase order %s cannot be amended: %s',
                $purchaseOrderId,
                $reason
            )
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
            'Purchase order validation failed: ' . json_encode($errors)
        );
    }
}
