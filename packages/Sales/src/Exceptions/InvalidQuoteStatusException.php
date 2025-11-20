<?php

declare(strict_types=1);

namespace Nexus\Sales\Exceptions;

use Nexus\Sales\Enums\QuoteStatus;

/**
 * Exception thrown when quote status transition is invalid.
 */
class InvalidQuoteStatusException extends SalesException
{
    public static function cannotTransition(string $quoteId, QuoteStatus $currentStatus, QuoteStatus $newStatus): self
    {
        return new self(
            "Cannot transition quote '{$quoteId}' from '{$currentStatus->value}' to '{$newStatus->value}'."
        );
    }

    public static function cannotSend(string $quoteId, QuoteStatus $status): self
    {
        return new self(
            "Cannot send quote '{$quoteId}' with status '{$status->value}'. " .
            "Quote must be in 'draft' status."
        );
    }

    public static function cannotConvert(string $quoteId, QuoteStatus $status): self
    {
        return new self(
            "Cannot convert quote '{$quoteId}' with status '{$status->value}' to order. " .
            "Quote must be 'accepted' before conversion."
        );
    }
}
