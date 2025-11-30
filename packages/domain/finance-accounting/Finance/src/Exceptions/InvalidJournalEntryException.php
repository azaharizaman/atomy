<?php

declare(strict_types=1);

namespace Nexus\Finance\Exceptions;

final class InvalidJournalEntryException extends FinanceException
{
    public static function emptyLines(): self
    {
        return new self('Journal entry must have at least two lines');
    }

    public static function invalidLine(string $reason): self
    {
        return new self("Invalid journal entry line: {$reason}");
    }
}
