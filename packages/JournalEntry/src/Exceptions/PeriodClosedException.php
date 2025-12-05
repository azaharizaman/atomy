<?php

declare(strict_types=1);

namespace Nexus\JournalEntry\Exceptions;

/**
 * Thrown when an operation is attempted on a closed fiscal period.
 */
class PeriodClosedException extends JournalEntryException
{
    public static function cannotPost(string $date): self
    {
        return new self("Cannot post journal entry: Fiscal period for {$date} is closed");
    }
}
