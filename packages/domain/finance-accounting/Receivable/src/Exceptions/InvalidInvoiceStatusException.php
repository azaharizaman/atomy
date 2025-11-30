<?php

declare(strict_types=1);

namespace Nexus\Receivable\Exceptions;

use Nexus\Receivable\Enums\InvoiceStatus;
use RuntimeException;

/**
 * Invalid Invoice Status Exception
 *
 * Thrown when an operation cannot be performed due to the invoice's current status.
 */
class InvalidInvoiceStatusException extends RuntimeException
{
    public static function cannotPost(string $invoiceId, InvoiceStatus $currentStatus): self
    {
        return new self(
            "Invoice {$invoiceId} cannot be posted to GL. Current status: {$currentStatus->label()}"
        );
    }

    public static function cannotApplyPayment(string $invoiceId, InvoiceStatus $currentStatus): self
    {
        return new self(
            "Invoice {$invoiceId} cannot receive payment. Current status: {$currentStatus->label()}"
        );
    }

    public static function cannotVoid(string $invoiceId, InvoiceStatus $currentStatus): self
    {
        return new self(
            "Invoice {$invoiceId} cannot be voided. Current status: {$currentStatus->label()}"
        );
    }

    public static function alreadyFinal(string $invoiceId, InvoiceStatus $currentStatus): self
    {
        return new self(
            "Invoice {$invoiceId} is in final status and cannot be modified: {$currentStatus->label()}"
        );
    }
}
