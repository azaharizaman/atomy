<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Exceptions;

/**
 * Exception for quote-related operations.
 */
class QuoteOperationException extends CRMOperationsException
{
    public static function notFound(string $quoteId): self
    {
        return new self(sprintf('Quote not found: %s', $quoteId), "quote_id: {$quoteId}");
    }

    public static function invalidStatus(string $quoteId, string $currentStatus): self
    {
        return new self(
            sprintf('Quote must be accepted before conversion. Current status: %s', $currentStatus),
            "quote_id: {$quoteId}, status: {$currentStatus}"
        );
    }
}
