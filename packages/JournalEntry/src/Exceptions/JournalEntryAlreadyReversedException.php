<?php

declare(strict_types=1);

namespace Nexus\JournalEntry\Exceptions;

/**
 * Thrown when an operation requires an unreversed entry but entry was already reversed.
 */
class JournalEntryAlreadyReversedException extends JournalEntryException
{
    public static function cannotReverse(string $id): self
    {
        return new self("Cannot reverse journal entry {$id}: entry has already been reversed");
    }
}
