<?php

declare(strict_types=1);

namespace Nexus\JournalEntry\Exceptions;

/**
 * Thrown when a journal entry is unbalanced (debits != credits).
 */
class UnbalancedJournalEntryException extends JournalEntryException
{
    public static function create(string $totalDebit, string $totalCredit, string $currency): self
    {
        return new self(
            "Journal entry is unbalanced: Total Debits = {$totalDebit} {$currency}, " .
            "Total Credits = {$totalCredit} {$currency}"
        );
    }
}
