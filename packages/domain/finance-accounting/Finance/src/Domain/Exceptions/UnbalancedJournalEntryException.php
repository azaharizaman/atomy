<?php

declare(strict_types=1);

namespace Nexus\Finance\Domain\Exceptions;

final class UnbalancedJournalEntryException extends FinanceException
{
    public static function create(string $totalDebit, string $totalCredit): self
    {
        return new self("Journal entry is unbalanced. Debits: {$totalDebit}, Credits: {$totalCredit}");
    }
}
