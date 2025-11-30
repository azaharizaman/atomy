<?php

declare(strict_types=1);

namespace Nexus\Finance\Domain\Exceptions;

final class JournalEntryAlreadyPostedException extends FinanceException
{
    public static function forEntry(string $entryId, string $entryNumber): self
    {
        return new self("Journal entry {$entryNumber} (ID: {$entryId}) is already posted and cannot be modified");
    }
}
