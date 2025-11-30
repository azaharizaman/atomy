<?php

declare(strict_types=1);

namespace Nexus\Finance\Domain\Exceptions;

final class JournalEntryNotFoundException extends FinanceException
{
    public static function forId(string $id): self
    {
        return new self("Journal entry not found with ID: {$id}");
    }

    public static function forEntryNumber(string $entryNumber): self
    {
        return new self("Journal entry not found with number: {$entryNumber}");
    }
}
