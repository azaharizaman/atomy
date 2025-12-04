<?php

declare(strict_types=1);

namespace Nexus\JournalEntry\Exceptions;

/**
 * Thrown when an attempt is made to modify a posted journal entry.
 */
class JournalEntryAlreadyPostedException extends JournalEntryException
{
    public static function cannotEdit(string $id): self
    {
        return new self("Cannot edit journal entry {$id}: entry is already posted");
    }

    public static function cannotDelete(string $id): self
    {
        return new self("Cannot delete journal entry {$id}: entry is already posted");
    }

    public static function cannotPost(string $id): self
    {
        return new self("Cannot post journal entry {$id}: entry is already posted");
    }
}
