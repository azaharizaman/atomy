<?php

declare(strict_types=1);

namespace Nexus\Finance\Infrastructure\Persistence;

use DateTimeImmutable;
use Nexus\Finance\Domain\Contracts\LedgerRepositoryInterface;

/**
 * In-Memory Ledger Repository
 * 
 * Internal adapter for testing and development purposes.
 * This repository provides ledger queries from in-memory data.
 */
final class InMemoryLedgerRepository implements LedgerRepositoryInterface
{
    /** @var array<string, array{date: DateTimeImmutable, debit: string, credit: string}[]> */
    private array $ledgerEntries = [];

    /** @var array<string, string> Opening balances by account */
    private array $openingBalances = [];

    /**
     * {@inheritDoc}
     */
    public function getAccountBalance(string $accountId, DateTimeImmutable $asOfDate): string
    {
        $balance = $this->openingBalances[$accountId] ?? '0.0000';

        if (!isset($this->ledgerEntries[$accountId])) {
            return $balance;
        }

        foreach ($this->ledgerEntries[$accountId] as $entry) {
            if ($entry['date'] <= $asOfDate) {
                $balance = bcadd($balance, $entry['debit'], 4);
                $balance = bcsub($balance, $entry['credit'], 4);
            }
        }

        return $balance;
    }

    /**
     * {@inheritDoc}
     */
    public function getTrialBalance(DateTimeImmutable $asOfDate): array
    {
        $result = [];

        foreach (array_keys($this->ledgerEntries) as $accountId) {
            $balance = $this->getAccountBalance($accountId, $asOfDate);

            // Determine if balance is debit or credit
            $isDebit = bccomp($balance, '0', 4) >= 0;

            // Note: In production, account_code and account_name should be looked up
            // from the AccountRepository. These placeholders are for testing only.
            $result[] = [
                'account_id' => $accountId,
                'account_code' => $accountId, // Testing placeholder - lookup in production
                'account_name' => "Account {$accountId}", // Testing placeholder
                'debit' => $isDebit ? $balance : '0.0000',
                'credit' => $isDebit ? '0.0000' : bcmul($balance, '-1', 4),
            ];
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getAccountActivity(
        string $accountId,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate
    ): array {
        $result = [];
        $runningBalance = $this->getOpeningBalance($accountId, $startDate);

        if (!isset($this->ledgerEntries[$accountId])) {
            return [];
        }

        foreach ($this->ledgerEntries[$accountId] as $entry) {
            if ($entry['date'] >= $startDate && $entry['date'] <= $endDate) {
                $runningBalance = bcadd($runningBalance, $entry['debit'], 4);
                $runningBalance = bcsub($runningBalance, $entry['credit'], 4);

                $result[] = [
                    'date' => $entry['date'],
                    'entry_number' => $entry['entry_number'] ?? 'N/A',
                    'description' => $entry['description'] ?? '',
                    'debit' => $entry['debit'],
                    'credit' => $entry['credit'],
                    'balance' => $runningBalance,
                ];
            }
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getOpeningBalance(string $accountId, DateTimeImmutable $periodStartDate): string
    {
        // Get balance up to the day before period start
        $dayBefore = $periodStartDate->modify('-1 day');
        return $this->getAccountBalance($accountId, $dayBefore);
    }

    /**
     * Add a ledger entry (for testing)
     * 
     * @param string $accountId
     * @param DateTimeImmutable $date
     * @param string $debit
     * @param string $credit
     * @param string|null $entryNumber
     * @param string|null $description
     */
    public function addLedgerEntry(
        string $accountId,
        DateTimeImmutable $date,
        string $debit,
        string $credit,
        ?string $entryNumber = null,
        ?string $description = null
    ): void {
        if (!isset($this->ledgerEntries[$accountId])) {
            $this->ledgerEntries[$accountId] = [];
        }

        $this->ledgerEntries[$accountId][] = [
            'date' => $date,
            'debit' => $debit,
            'credit' => $credit,
            'entry_number' => $entryNumber,
            'description' => $description,
        ];
    }

    /**
     * Set opening balance for an account (for testing)
     */
    public function setOpeningBalance(string $accountId, string $balance): void
    {
        $this->openingBalances[$accountId] = $balance;
    }

    /**
     * Clear all ledger data (for testing)
     */
    public function clear(): void
    {
        $this->ledgerEntries = [];
        $this->openingBalances = [];
    }
}
