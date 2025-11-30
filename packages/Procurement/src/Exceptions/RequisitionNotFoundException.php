<?php

declare(strict_types=1);

namespace Nexus\Procurement\Exceptions;

/**
 * Exception thrown when requisition is not found.
 */
class RequisitionNotFoundException extends ProcurementException
{
    public static function forId(string $id): self
    {
        return new self("Requisition with ID '{$id}' not found.");
    }

    public static function forNumber(string $tenantId, string $number): self
    {
        return new self("Requisition '{$number}' not found for tenant '{$tenantId}'.");
    }
}
