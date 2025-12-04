<?php

declare(strict_types=1);

namespace Nexus\JournalEntry\Exceptions;

/**
 * Thrown when an operation requires a posted entry but entry is not posted.
 */
class JournalEntryNotPostedException extends JournalEntryException
{
    public static function cannotReverse(string $id): self
    {
        return new self("Cannot reverse journal entry {$id}: entry is not posted");
    }
}
