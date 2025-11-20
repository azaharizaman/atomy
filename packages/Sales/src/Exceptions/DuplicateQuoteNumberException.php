<?php

declare(strict_types=1);

namespace Nexus\Sales\Exceptions;

/**
 * Exception thrown when duplicate quote number is detected.
 */
class DuplicateQuoteNumberException extends SalesException
{
    public static function forNumber(string $tenantId, string $quoteNumber): self
    {
        return new self(
            "Quote number '{$quoteNumber}' already exists for tenant '{$tenantId}'. " .
            "Quote numbers must be unique within a tenant."
        );
    }
}
