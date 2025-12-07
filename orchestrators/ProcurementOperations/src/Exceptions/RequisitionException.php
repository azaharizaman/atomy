<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Exceptions;

/**
 * Exception for requisition-related errors.
 */
class RequisitionException extends ProcurementOperationsException
{
    /**
     * Create exception for requisition not found.
     */
    public static function notFound(string $requisitionId): self
    {
        return new self(
            sprintf('Requisition not found: %s', $requisitionId)
        );
    }

    /**
     * Create exception for invalid requisition status.
     */
    public static function invalidStatus(string $requisitionId, string $currentStatus, string $requiredStatus): self
    {
        return new self(
            sprintf(
                'Requisition %s has invalid status "%s", expected "%s"',
                $requisitionId,
                $currentStatus,
                $requiredStatus
            )
        );
    }

    /**
     * Create exception for requisition already approved.
     */
    public static function alreadyApproved(string $requisitionId): self
    {
        return new self(
            sprintf('Requisition %s has already been approved', $requisitionId)
        );
    }

    /**
     * Create exception for requisition already rejected.
     */
    public static function alreadyRejected(string $requisitionId): self
    {
        return new self(
            sprintf('Requisition %s has already been rejected', $requisitionId)
        );
    }

    /**
     * Create exception for requisition with PO.
     */
    public static function hasAssociatedPurchaseOrder(string $requisitionId, string $purchaseOrderId): self
    {
        return new self(
            sprintf(
                'Requisition %s cannot be cancelled because it has associated PO: %s',
                $requisitionId,
                $purchaseOrderId
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
            'Requisition validation failed: ' . json_encode($errors)
        );
    }
}
