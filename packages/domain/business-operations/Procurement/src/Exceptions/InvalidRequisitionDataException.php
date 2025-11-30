<?php

declare(strict_types=1);

namespace Nexus\Procurement\Exceptions;

/**
 * Exception thrown when requisition data is invalid.
 */
class InvalidRequisitionDataException extends ProcurementException
{
    public static function noLines(): self
    {
        return new self("Requisition must have at least one line item.");
    }

    public static function invalidTotalEstimate(float $providedTotal, float $calculatedTotal): self
    {
        return new self(
            "Requisition total estimate ({$providedTotal}) does not match sum of line items ({$calculatedTotal})."
        );
    }

    public static function missingRequiredField(string $field): self
    {
        return new self("Required field '{$field}' is missing from requisition data.");
    }
}
