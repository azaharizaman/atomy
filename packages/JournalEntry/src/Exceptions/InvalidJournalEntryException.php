<?php

declare(strict_types=1);

namespace Nexus\JournalEntry\Exceptions;

/**
 * Thrown when a journal entry fails validation.
 */
class InvalidJournalEntryException extends JournalEntryException
{
    public static function emptyDescription(): self
    {
        return new self('Journal entry description cannot be empty');
    }

    public static function noLines(): self
    {
        return new self('Journal entry must have at least two line items');
    }

    public static function insufficientLines(int $count): self
    {
        return new self("Journal entry must have at least 2 line items, got {$count}");
    }

    public static function invalidDate(): self
    {
        return new self('Journal entry posting date is invalid');
    }

    public static function invalidLine(int $index, string $reason): self
    {
        return new self("Invalid line item at index {$index}: {$reason}");
    }
}
