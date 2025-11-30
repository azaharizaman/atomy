<?php

declare(strict_types=1);

namespace Nexus\Procurement\Exceptions;

/**
 * Exception thrown when goods receipt note is not found.
 */
class GoodsReceiptNotFoundException extends ProcurementException
{
    public static function forId(string $id): self
    {
        return new self("Goods receipt note with ID '{$id}' not found.");
    }

    public static function forNumber(string $tenantId, string $number): self
    {
        return new self("GRN '{$number}' not found for tenant '{$tenantId}'.");
    }
}
