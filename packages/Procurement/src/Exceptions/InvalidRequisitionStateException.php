<?php

declare(strict_types=1);

namespace Nexus\Procurement\Exceptions;

/**
 * Exception thrown when requisition state is invalid for operation.
 */
class InvalidRequisitionStateException extends ProcurementException
{
    public static function cannotApproveStatus(string $id, string $status): self
    {
        return new self("Cannot approve requisition '{$id}' with status '{$status}'.");
    }

    public static function cannotConvertStatus(string $id, string $status): self
    {
        return new self("Cannot convert requisition '{$id}' with status '{$status}' to PO. Must be 'approved'.");
    }

    public static function cannotEditApproved(string $id): self
    {
        return new self("Cannot edit requisition '{$id}' - approved requisitions are immutable.");
    }

    public static function alreadyConverted(string $id): self
    {
        return new self("Requisition '{$id}' has already been converted to a purchase order.");
    }
}
