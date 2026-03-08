<?php

declare(strict_types=1);

namespace Nexus\Procurement\Exceptions;

/**
 * Exception thrown when a purchase order is in an invalid status for the requested operation.
 */
class InvalidPurchaseOrderStatusException extends ProcurementException
{
    public static function forOperation(string $id, string $status, string $operation): self
    {
        return new self("Purchase order '{$id}' cannot be {$operation} because it is in '{$status}' status.");
    }

    public static function forRelease(string $id, string $status): self
    {
        return self::forOperation($id, $status, 'released');
    }
}
