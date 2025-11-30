<?php

declare(strict_types=1);

namespace Nexus\Receivable\Exceptions;

use RuntimeException;

/**
 * Invoice Not Found Exception
 *
 * Thrown when a requested invoice cannot be found.
 */
class InvoiceNotFoundException extends RuntimeException
{
    public static function forId(string $invoiceId): self
    {
        return new self("Invoice not found: {$invoiceId}");
    }

    public static function forNumber(string $invoiceNumber): self
    {
        return new self("Invoice not found with number: {$invoiceNumber}");
    }
}
