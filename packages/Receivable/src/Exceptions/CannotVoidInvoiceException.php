<?php

declare(strict_types=1);

namespace Nexus\Receivable\Exceptions;

use Nexus\Receivable\Enums\InvoiceStatus;
use RuntimeException;

/**
 * Cannot Void Invoice Exception
 *
 * Thrown when an invoice cannot be voided due to business rules.
 */
class CannotVoidInvoiceException extends RuntimeException
{
    public static function dueToStatus(string $invoiceId, InvoiceStatus $status): self
    {
        return new self(
            "Cannot void invoice {$invoiceId}: Current status {$status->label()} does not allow voiding"
        );
    }

    public static function hasPayments(string $invoiceId): self
    {
        return new self(
            "Cannot void invoice {$invoiceId}: Invoice has received payments. Reverse payments first."
        );
    }

    public static function glPosted(string $invoiceId, string $journalId): self
    {
        return new self(
            "Cannot void invoice {$invoiceId}: GL journal entry {$journalId} already posted. Create reversing entry."
        );
    }
}
