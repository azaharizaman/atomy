<?php

declare(strict_types=1);

namespace Nexus\Procurement\Exceptions;

/**
 * Exception thrown when goods receipt data is invalid.
 */
class InvalidGoodsReceiptDataException extends ProcurementException
{
    public static function noLines(): self
    {
        return new self("Goods receipt note must have at least one line item.");
    }

    public static function quantityExceedsPo(string $poLineRef, float $grnQty, float $poQty): self
    {
        return new self(
            "GRN quantity ({$grnQty}) for PO line '{$poLineRef}' exceeds ordered quantity ({$poQty})."
        );
    }

    public static function missingPoLineReference(int $lineNumber): self
    {
        return new self("GRN line {$lineNumber} is missing PO line reference.");
    }

    public static function missingRequiredField(string $field): self
    {
        return new self("Required field '{$field}' is missing from GRN data.");
    }
}
