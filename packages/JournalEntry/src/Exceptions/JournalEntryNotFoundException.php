<?php

declare(strict_types=1);

namespace Nexus\JournalEntry\Exceptions;

/**
 * Thrown when a journal entry is not found.
 */
class JournalEntryNotFoundException extends JournalEntryException
{
    public static function withId(string $id): self
    {
        return new self("Journal entry not found with ID: {$id}");
    }

    public static function withNumber(string $number): self
    {
        return new self("Journal entry not found with number: {$number}");
    }
}
