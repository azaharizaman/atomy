<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts\Integration;

interface FinancePostingInterface
{
    public function postJournalEntry(array $entryData): string;

    public function getAccountBalance(string $accountId, string $asOfDate): float;

    public function validateAccount(string $accountId): bool;

    public function getAccountCurrency(string $accountId): string;
}
