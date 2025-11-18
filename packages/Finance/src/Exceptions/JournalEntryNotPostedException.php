<?php

declare(strict_types=1);

namespace Nexus\Finance\Exceptions;

final class JournalEntryNotPostedException extends FinanceException
{
    public static function forEntry(string $entryId): self
    {
        return new self("Journal entry (ID: {$entryId}) has not been posted yet");
    }
}
