<?php

declare(strict_types=1);

namespace Nexus\Receivable\Exceptions;

use RuntimeException;

/**
 * Invoice Already Paid Exception
 *
 * Thrown when attempting to modify or apply payment to a fully paid invoice.
 */
class InvoiceAlreadyPaidException extends RuntimeException
{
    public static function forInvoice(string $invoiceId): self
    {
        return new self("Invoice {$invoiceId} is already fully paid and cannot be modified");
    }

    public static function cannotApplyAdditionalPayment(string $invoiceId): self
    {
        return new self("Cannot apply additional payment to invoice {$invoiceId}: invoice is already fully paid");
    }
}
