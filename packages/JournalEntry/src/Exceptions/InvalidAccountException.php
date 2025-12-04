<?php

declare(strict_types=1);

namespace Nexus\JournalEntry\Exceptions;

/**
 * Thrown when an account cannot be found or is invalid.
 */
class InvalidAccountException extends JournalEntryException
{
    public static function notFound(string $accountId): self
    {
        return new self("Account not found: {$accountId}");
    }

    public static function inactive(string $accountId): self
    {
        return new self("Account is inactive: {$accountId}");
    }

    public static function postingNotAllowed(string $accountId): self
    {
        return new self("Account does not allow posting (is a header): {$accountId}");
    }
}
