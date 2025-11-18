<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Nexus\Finance\Contracts\LedgerRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Eloquent Ledger Repository
 * 
 * Implements LedgerRepositoryInterface for read-only ledger queries.
 */
final readonly class EloquentLedgerRepository implements LedgerRepositoryInterface
{
    public function getAccountBalance(
        string $accountId,
        ?string $asOfDate = null,
        ?string $periodId = null
    ): string {
        $query = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entry_lines.account_id', $accountId)
            ->where('journal_entries.status', 'posted');

        if ($asOfDate) {
            $query->where('journal_entries.entry_date', '<=', $asOfDate);
        }

        if ($periodId) {
            $query->where('journal_entries.period_id', $periodId);
        }

        $totals = $query->selectRaw('
            SUM(journal_entry_lines.debit_amount) as total_debit,
            SUM(journal_entry_lines.credit_amount) as total_credit
        ')->first();

        $debit = $totals->total_debit ?? '0.0000';
        $credit = $totals->total_credit ?? '0.0000';

        $account = Account::find($accountId);
        $accountType = $account ? $account->type : 'asset';

        if (in_array($accountType, ['asset', 'expense'])) {
            return bcsub($debit, $credit, 4);
        } else {
            return bcsub($credit, $debit, 4);
        }
    }

    public function getAccountActivity(
        string $accountId,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $query = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entry_lines.account_id', $accountId)
            ->where('journal_entries.status', 'posted')
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.entry_number');

        if ($startDate) {
            $query->where('journal_entries.entry_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('journal_entries.entry_date', '<=', $endDate);
        }

        $lines = $query->select('journal_entry_lines.*', 'journal_entries.entry_date', 'journal_entries.entry_number')
            ->get();

        $runningBalance = '0.0000';
        $account = Account::find($accountId);
        $isDebitNormal = in_array($account->type, ['asset', 'expense']);

        return $lines->map(function ($line) use (&$runningBalance, $isDebitNormal) {
            $debit = $line->debit_amount;
            $credit = $line->credit_amount;

            if ($isDebitNormal) {
                $runningBalance = bcadd($runningBalance, bcsub($debit, $credit, 4), 4);
            } else {
                $runningBalance = bcadd($runningBalance, bcsub($credit, $debit, 4), 4);
            }

            return [
                'date' => $line->entry_date,
                'entry_number' => $line->entry_number,
                'description' => $line->description,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $runningBalance,
            ];
        })->all();
    }

    public function getTrialBalance(?string $asOfDate = null): array
    {
        $query = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->where('journal_entries.status', 'posted')
            ->where('accounts.is_header', false);

        if ($asOfDate) {
            $query->where('journal_entries.entry_date', '<=', $asOfDate);
        }

        $results = $query->selectRaw('
            accounts.id,
            accounts.code,
            accounts.name,
            accounts.type,
            SUM(journal_entry_lines.debit_amount) as total_debit,
            SUM(journal_entry_lines.credit_amount) as total_credit
        ')
        ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type')
        ->orderBy('accounts.code')
        ->get();

        return $results->map(function ($row) {
            $debit = $row->total_debit ?? '0.0000';
            $credit = $row->total_credit ?? '0.0000';

            if (in_array($row->type, ['asset', 'expense'])) {
                $balance = bcsub($debit, $credit, 4);
            } else {
                $balance = bcsub($credit, $debit, 4);
            }

            return [
                'account_id' => $row->id,
                'account_code' => $row->code,
                'account_name' => $row->name,
                'account_type' => $row->type,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $balance,
            ];
        })->all();
    }

    public function getGeneralLedger(
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $accountId = null
    ): array {
        $query = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->where('journal_entries.status', 'posted')
            ->orderBy('accounts.code')
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.entry_number');

        if ($startDate) {
            $query->where('journal_entries.entry_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('journal_entries.entry_date', '<=', $endDate);
        }

        if ($accountId) {
            $query->where('journal_entry_lines.account_id', $accountId);
        }

        return $query->select(
            'journal_entry_lines.*',
            'journal_entries.entry_date',
            'journal_entries.entry_number',
            'journal_entries.description as entry_description',
            'accounts.code as account_code',
            'accounts.name as account_name'
        )->get()->all();
    }
}
