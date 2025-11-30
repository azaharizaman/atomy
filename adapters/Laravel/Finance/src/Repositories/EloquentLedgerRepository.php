<?php

declare(strict_types=1);

namespace Nexus\Laravel\Finance\Repositories;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Nexus\Finance\Domain\Contracts\LedgerRepositoryInterface;
use Nexus\Laravel\Finance\Models\Account;
use Nexus\Laravel\Finance\Models\JournalEntry;
use Nexus\Laravel\Finance\Models\JournalEntryLine;

/**
 * Eloquent implementation of Ledger Repository
 */
final readonly class EloquentLedgerRepository implements LedgerRepositoryInterface
{
    public function getAccountBalance(string $accountId, DateTimeImmutable $asOfDate): string
    {
        $account = Account::find($accountId);
        
        if ($account === null) {
            return '0.0000';
        }
        
        $debits = JournalEntryLine::where('account_id', $accountId)
            ->whereHas('journalEntry', function ($q) use ($asOfDate) {
                $q->where('date', '<=', $asOfDate->format('Y-m-d'))
                    ->where('status', 'posted');
            })
            ->sum('debit_amount');
        
        $credits = JournalEntryLine::where('account_id', $accountId)
            ->whereHas('journalEntry', function ($q) use ($asOfDate) {
                $q->where('date', '<=', $asOfDate->format('Y-m-d'))
                    ->where('status', 'posted');
            })
            ->sum('credit_amount');
        
        // For Asset and Expense accounts, debit increases the balance
        // For Liability, Equity, and Revenue accounts, credit increases the balance
        $accountType = strtoupper($account->type);
        
        if (in_array($accountType, ['ASSET', 'EXPENSE'], true)) {
            return bcsub((string) $debits, (string) $credits, 4);
        }
        
        return bcsub((string) $credits, (string) $debits, 4);
    }

    /**
     * @return array<array{account_id: string, account_code: string, account_name: string, debit: string, credit: string}>
     */
    public function getTrialBalance(DateTimeImmutable $asOfDate): array
    {
        $accounts = Account::where('is_active', true)
            ->where('is_header', false)
            ->orderBy('code')
            ->get();
        
        $trialBalance = [];
        
        foreach ($accounts as $account) {
            $debits = JournalEntryLine::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($asOfDate) {
                    $q->where('date', '<=', $asOfDate->format('Y-m-d'))
                        ->where('status', 'posted');
                })
                ->sum('debit_amount');
            
            $credits = JournalEntryLine::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($asOfDate) {
                    $q->where('date', '<=', $asOfDate->format('Y-m-d'))
                        ->where('status', 'posted');
                })
                ->sum('credit_amount');
            
            // Only include accounts with activity
            if (bccomp((string) $debits, '0', 4) !== 0 || bccomp((string) $credits, '0', 4) !== 0) {
                $trialBalance[] = [
                    'account_id' => $account->id,
                    'account_code' => $account->code,
                    'account_name' => $account->name,
                    'debit' => bcadd((string) $debits, '0', 4),
                    'credit' => bcadd((string) $credits, '0', 4),
                ];
            }
        }
        
        return $trialBalance;
    }

    /**
     * @return array<array{date: DateTimeImmutable, entry_number: string, description: string, debit: string, credit: string, balance: string}>
     */
    public function getAccountActivity(
        string $accountId,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate
    ): array {
        $account = Account::find($accountId);
        
        if ($account === null) {
            return [];
        }
        
        // Get opening balance
        $openingBalance = $this->getOpeningBalance($accountId, $startDate);
        
        $lines = JournalEntryLine::with('journalEntry')
            ->where('account_id', $accountId)
            ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d'),
                ])
                ->where('status', 'posted');
            })
            ->get()
            ->sortBy(fn ($line) => $line->journalEntry->date);
        
        $activity = [];
        $runningBalance = $openingBalance;
        $isDebitNormal = in_array(strtoupper($account->type), ['ASSET', 'EXPENSE'], true);
        
        foreach ($lines as $line) {
            $debit = bcadd((string) $line->debit_amount, '0', 4);
            $credit = bcadd((string) $line->credit_amount, '0', 4);
            
            // Calculate running balance
            if ($isDebitNormal) {
                $runningBalance = bcadd(
                    bcsub($runningBalance, $credit, 4),
                    $debit,
                    4
                );
            } else {
                $runningBalance = bcadd(
                    bcsub($runningBalance, $debit, 4),
                    $credit,
                    4
                );
            }
            
            $activity[] = [
                'date' => DateTimeImmutable::createFromMutable($line->journalEntry->date->toDateTime()),
                'entry_number' => $line->journalEntry->entry_number,
                'description' => $line->description ?? $line->journalEntry->description,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $runningBalance,
            ];
        }
        
        return $activity;
    }

    public function getOpeningBalance(string $accountId, DateTimeImmutable $periodStartDate): string
    {
        // Calculate balance as of the day before the period starts
        $asOfDate = $periodStartDate->modify('-1 day');
        
        return $this->getAccountBalance($accountId, $asOfDate);
    }
}
