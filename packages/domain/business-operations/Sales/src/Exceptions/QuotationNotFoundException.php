<?php

declare(strict_types=1);

namespace Nexus\Sales\Exceptions;

/**
 * Exception thrown when quotation is not found.
 */
class QuotationNotFoundException extends SalesException
{
    public static function forId(string $id): self
    {
        return new self("Quotation with ID '{$id}' not found.");
    }

    public static function forNumber(string $tenantId, string $quoteNumber): self
    {
        return new self("Quotation '{$quoteNumber}' not found for tenant '{$tenantId}'.");
    }
}
