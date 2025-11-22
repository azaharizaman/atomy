<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Finance\Account;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\JournalEntryLine;
use App\Models\Infrastructure\EventStream;
use App\Repositories\Finance\EloquentAccountRepository;
use App\Repositories\Finance\EloquentJournalEntryRepository;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Nexus\EventStream\Contracts\EventStoreInterface;
use Nexus\Finance\Enums\AccountType;
use Nexus\Finance\Enums\JournalEntryStatus;

/**
 * Finance Controller
 * 
 * Handles Finance (General Ledger) API operations.
 */
final class FinanceController extends Controller
{
    public function __construct(
        private readonly EloquentAccountRepository $accountRepository,
        private readonly EloquentJournalEntryRepository $journalEntryRepository,
        private readonly EventStoreInterface $eventStore
    ) {}

    /**
     * List all accounts
     */
    public function listAccounts(Request $request): JsonResponse
    {
        $filters = $request->only(['type', 'active', 'parent_id', 'is_header']);
        
        $accounts = $this->accountRepository->findAll($filters);

        return response()->json([
            'data' => array_map(fn($account) => [
                'id' => $account->getId(),
                'code' => $account->getCode(),
                'name' => $account->getName(),
                'type' => $account->getType(),
                'currency' => $account->getCurrency(),
                'parent_id' => $account->getParentId(),
                'is_header' => $account->isHeader(),
                'is_active' => $account->isActive(),
                'description' => $account->getDescription(),
            ], $accounts)
        ]);
    }

    /**
     * Create a new account
     */
    public function createAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:accounts,code',
            'name' => 'required|string|max:255',
            'account_type' => 'required|in:asset,liability,equity,revenue,expense',
            'currency' => 'nullable|string|size:3',
            'parent_id' => 'nullable|exists:accounts,id',
            'is_header' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        $account = Account::factory()->make($validated);
        $this->accountRepository->save($account);

        return response()->json([
            'data' => [
                'id' => $account->getId(),
                'code' => $account->getCode(),
                'name' => $account->getName(),
                'type' => $account->getType(),
            ]
        ], Response::HTTP_CREATED);
    }

    /**
     * Get account by ID
     */
    public function getAccount(string $id): JsonResponse
    {
        $account = $this->accountRepository->find($id);

        if ($account === null) {
            return response()->json(['error' => 'Account not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => [
                'id' => $account->getId(),
                'code' => $account->getCode(),
                'name' => $account->getName(),
                'type' => $account->getType(),
                'currency' => $account->getCurrency(),
                'parent_id' => $account->getParentId(),
                'is_header' => $account->isHeader(),
                'is_active' => $account->isActive(),
                'description' => $account->getDescription(),
            ]
        ]);
    }

    /**
     * Get account by code
     */
    public function getAccountByCode(string $code): JsonResponse
    {
        $account = $this->accountRepository->findByCode($code);

        if ($account === null) {
            return response()->json(['error' => 'Account not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => [
                'id' => $account->getId(),
                'code' => $account->getCode(),
                'name' => $account->getName(),
                'type' => $account->getType(),
            ]
        ]);
    }

    /**
     * Update account
     */
    public function updateAccount(Request $request, string $id): JsonResponse
    {
        $account = Account::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean',
            'description' => 'nullable|string',
        ]);

        $account->fill($validated);
        $this->accountRepository->save($account);

        return response()->json([
            'data' => [
                'id' => $account->getId(),
                'code' => $account->getCode(),
                'name' => $account->getName(),
            ]
        ]);
    }

    /**
     * Delete account
     */
    public function deleteAccount(string $id): JsonResponse
    {
        $this->accountRepository->delete($id);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Get account balance (stub - requires ledger implementation)
     */
    public function getAccountBalance(string $id, Request $request): JsonResponse
    {
        $account = $this->accountRepository->find($id);

        if ($account === null) {
            return response()->json(['error' => 'Account not found'], Response::HTTP_NOT_FOUND);
        }

        // TODO: Implement actual balance calculation via LedgerRepository
        return response()->json([
            'data' => [
                'account_id' => $account->getId(),
                'account_code' => $account->getCode(),
                'balance' => '0.0000',
                'currency' => $account->getCurrency(),
                'as_of_date' => now()->toDateString(),
            ]
        ]);
    }

    /**
     * Get chart of accounts
     */
    public function getChartOfAccounts(): JsonResponse
    {
        $accounts = $this->accountRepository->findAll(['active' => true]);

        // Group by account type
        $chartOfAccounts = [
            'assets' => [],
            'liabilities' => [],
            'equity' => [],
            'revenue' => [],
            'expenses' => [],
        ];

        foreach ($accounts as $account) {
            $data = [
                'id' => $account->getId(),
                'code' => $account->getCode(),
                'name' => $account->getName(),
                'is_header' => $account->isHeader(),
            ];

            match (AccountType::from($account->getType())) {
                AccountType::Asset => $chartOfAccounts['assets'][] = $data,
                AccountType::Liability => $chartOfAccounts['liabilities'][] = $data,
                AccountType::Equity => $chartOfAccounts['equity'][] = $data,
                AccountType::Revenue => $chartOfAccounts['revenue'][] = $data,
                AccountType::Expense => $chartOfAccounts['expenses'][] = $data,
            };
        }

        return response()->json(['data' => $chartOfAccounts]);
    }

    /**
     * List journal entries
     */
    public function listJournalEntries(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'start_date', 'end_date', 'created_by']);
        
        $entries = $this->journalEntryRepository->findAll($filters);

        return response()->json([
            'data' => array_map(fn($entry) => [
                'id' => $entry->getId(),
                'entry_number' => $entry->getEntryNumber(),
                'date' => $entry->getDate()->format('Y-m-d'),
                'description' => $entry->getDescription(),
                'status' => $entry->getStatus(),
                'is_balanced' => $entry->isBalanced(),
                'total_debit' => $entry->getTotalDebit(),
                'total_credit' => $entry->getTotalCredit(),
            ], $entries)
        ]);
    }

    /**
     * Create journal entry
     */
    public function createJournalEntry(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entry_date' => 'required|date',
            'description' => 'required|string',
            'reference' => 'nullable|string|max:100',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.debit_amount' => 'required_without:lines.*.credit_amount|numeric|min:0',
            'lines.*.credit_amount' => 'required_without:lines.*.debit_amount|numeric|min:0',
            'lines.*.description' => 'nullable|string',
        ]);

        $entryDate = new DateTimeImmutable($validated['entry_date']);
        $entryNumber = $this->journalEntryRepository->getNextEntryNumber($entryDate);

        $entry = JournalEntry::factory()->make([
            'entry_number' => $entryNumber,
            'entry_date' => $validated['entry_date'],
            'description' => $validated['description'],
            'reference' => $validated['reference'] ?? null,
            'status' => JournalEntryStatus::Draft,
            'created_by' => auth()->id(),
        ]);

        $this->journalEntryRepository->save($entry);

        // Create lines
        foreach ($validated['lines'] as $lineData) {
            JournalEntryLine::factory()->make([
                'journal_entry_id' => $entry->id,
                'account_id' => $lineData['account_id'],
                'debit_amount' => $lineData['debit_amount'] ?? 0,
                'credit_amount' => $lineData['credit_amount'] ?? 0,
                'description' => $lineData['description'] ?? null,
            ])->save();
        }

        return response()->json([
            'data' => [
                'id' => $entry->getId(),
                'entry_number' => $entry->getEntryNumber(),
                'status' => $entry->getStatus(),
            ]
        ], Response::HTTP_CREATED);
    }

    /**
     * Get journal entry by ID
     */
    public function getJournalEntry(string $id): JsonResponse
    {
        $entry = $this->journalEntryRepository->find($id);

        if ($entry === null) {
            return response()->json(['error' => 'Journal entry not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => [
                'id' => $entry->getId(),
                'entry_number' => $entry->getEntryNumber(),
                'date' => $entry->getDate()->format('Y-m-d'),
                'description' => $entry->getDescription(),
                'reference' => $entry->getReference(),
                'status' => $entry->getStatus(),
                'is_balanced' => $entry->isBalanced(),
                'lines' => array_map(fn($line) => [
                    'id' => $line->getId(),
                    'account_id' => $line->getAccountId(),
                    'debit_amount' => $line->getDebitAmount()->getAmount(),
                    'credit_amount' => $line->getCreditAmount()->getAmount(),
                    'description' => $line->getDescription(),
                ], $entry->getLines()),
            ]
        ]);
    }

    /**
     * Get journal entry by entry number
     */
    public function getJournalEntryByNumber(string $entryNumber): JsonResponse
    {
        $entry = $this->journalEntryRepository->findByEntryNumber($entryNumber);

        if ($entry === null) {
            return response()->json(['error' => 'Journal entry not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => [
                'id' => $entry->getId(),
                'entry_number' => $entry->getEntryNumber(),
                'status' => $entry->getStatus(),
            ]
        ]);
    }

    /**
     * Update journal entry (draft only)
     */
    public function updateJournalEntry(Request $request, string $id): JsonResponse
    {
        $entry = JournalEntry::with('lines')->findOrFail($id);

        if ($entry->status !== JournalEntryStatus::Draft) {
            return response()->json(['error' => 'Only draft entries can be updated'], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'description' => 'sometimes|string',
            'reference' => 'nullable|string|max:100',
        ]);

        $entry->fill($validated);
        $this->journalEntryRepository->save($entry);

        return response()->json([
            'data' => [
                'id' => $entry->getId(),
                'entry_number' => $entry->getEntryNumber(),
            ]
        ]);
    }

    /**
     * Delete journal entry (draft only)
     */
    public function deleteJournalEntry(string $id): JsonResponse
    {
        $this->journalEntryRepository->delete($id);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Post journal entry with EventStream integration
     */
    public function postJournalEntry(string $id): JsonResponse
    {
        $entry = JournalEntry::with('lines')->findOrFail($id);

        if ($entry->status !== JournalEntryStatus::Draft) {
            return response()->json(['error' => 'Only draft entries can be posted'], Response::HTTP_FORBIDDEN);
        }

        if (!$entry->isBalanced()) {
            return response()->json(['error' => 'Entry is not balanced'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Publish AccountDebited/AccountCredited events to EventStream for GL compliance
        foreach ($entry->lines as $line) {
            $eventType = $line->isDebit() ? 'AccountDebited' : 'AccountCredited';
            $amount = $line->isDebit() 
                ? $line->getDebitAmount()->getAmount() 
                : $line->getCreditAmount()->getAmount();

            $event = EventStream::factory()->make([
                'aggregate_id' => $line->account_id,
                'event_type' => $eventType,
                'payload' => [
                    'journal_entry_id' => $entry->id,
                    'journal_entry_number' => $entry->entry_number,
                    'account_id' => $line->account_id,
                    'amount' => $amount,
                    'currency' => $line->isDebit() ? $line->debit_currency : $line->credit_currency,
                    'description' => $line->description ?? $entry->description,
                    'posted_by' => auth()->id(),
                ],
                'metadata' => [
                    'source' => 'FinanceController::postJournalEntry',
                    'entry_date' => $entry->entry_date->toDateString(),
                ],
            ]);

            $this->eventStore->append($line->account_id, $event);
        }
        
        $entry->status = JournalEntryStatus::Posted;
        $entry->posted_at = now();
        $entry->posted_by = auth()->id();
        $this->journalEntryRepository->save($entry);

        return response()->json([
            'data' => [
                'id' => $entry->getId(),
                'entry_number' => $entry->getEntryNumber(),
                'status' => $entry->getStatus(),
                'events_published' => $entry->lines->count(),
            ]
        ]);
    }

    /**
     * Reverse journal entry (stub)
     */
    public function reverseJournalEntry(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'reversal_date' => 'required|date',
            'reason' => 'required|string',
        ]);

        $entry = JournalEntry::with('lines')->findOrFail($id);

        if ($entry->status !== JournalEntryStatus::Posted) {
            return response()->json(['error' => 'Only posted entries can be reversed'], Response::HTTP_FORBIDDEN);
        }

        // TODO: Implement reversal logic with EventStream integration
        
        return response()->json([
            'message' => 'Reversal functionality pending EventStream integration'
        ], Response::HTTP_NOT_IMPLEMENTED);
    }
}
