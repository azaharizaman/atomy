<?php

declare(strict_types=1);

namespace Nexus\JournalEntry\Exceptions;

/**
 * Thrown when a journal entry number is invalid.
 */
class InvalidJournalEntryNumberException extends JournalEntryException
{
    public static function empty(): self
    {
        return new self('Journal entry number cannot be empty');
    }

    public static function invalidFormat(string $value): self
    {
        return new self("Invalid journal entry number format: {$value}");
    }

    public static function alreadyExists(string $number): self
    {
        return new self("Journal entry number already exists: {$number}");
    }
}
