<?php

declare(strict_types=1);

namespace Nexus\Procurement\Exceptions;

/**
 * Exception thrown when user is not authorized to perform an action.
 */
class UnauthorizedApprovalException extends ProcurementException
{
    public static function cannotApproveOwnRequisition(string $requisitionId, string $userId): self
    {
        return new self(
            "User '{$userId}' cannot approve requisition '{$requisitionId}' - requester cannot approve own requisition."
        );
    }

    public static function cannotCreateGrnForOwnPo(string $poId, string $userId): self
    {
        return new self(
            "User '{$userId}' cannot create GRN for PO '{$poId}' - PO creator cannot create GRN for same PO."
        );
    }

    public static function cannotAuthorizePaymentForOwnGrn(string $grnId, string $userId): self
    {
        return new self(
            "User '{$userId}' cannot authorize payment for GRN '{$grnId}' - GRN creator cannot authorize payment."
        );
    }
}
