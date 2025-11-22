<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Nexus\Finance\Contracts\FinanceManagerInterface;
use Nexus\Finance\Contracts\LedgerRepositoryInterface;
use Nexus\Finance\Exceptions\FinanceException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Finance API Controller
 * 
 * Handles all finance-related API endpoints for general ledger and journal entries.
 */
class FinanceController extends Controller
{
    public function __construct(
        private readonly FinanceManagerInterface $financeManager,
        private readonly LedgerRepositoryInterface $ledgerRepository
    ) {}

    /**
     * List all accounts
     */
    public function listAccounts(Request $request): JsonResponse
    {
        $accounts = $this->financeManager->getChartOfAccounts();

        return response()->json([
            'success' => true,
            'data' => array_map(fn($account) => [
                'id' => $account->getId(),
                'code' => $account->getCode(),
                'name' => $account->getName(),
                'type' => $account->getType(),
                'currency' => $account->getCurrency(),
                'is_header' => $account->isHeader(),
                'is_active' => $account->isActive(),
                'parent_id' => $account->getParentId(),
            ], $accounts)
        ]);
    }

    /**
     * Get a single account by ID
     */
    public function getAccount(string $id): JsonResponse
    {
        try {
            $account = $this->financeManager->getAccount($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $account->getId(),
                    'code' => $account->getCode(),
                    'name' => $account->getName(),
                    'type' => $account->getType(),
                    'currency' => $account->getCurrency(),
                    'is_header' => $account->isHeader(),
                    'is_active' => $account->isActive(),
                    'parent_id' => $account->getParentId(),
                    'description' => $account->getDescription(),
                ]
            ]);
        } catch (FinanceException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get account balance
     */
    public function getAccountBalance(string $id, Request $request): JsonResponse
    {
        try {
            $asOfDate = $request->query('as_of_date');
            $balance = $this->financeManager->getAccountBalance($id, $asOfDate);

            return response()->json([
                'success' => true,
                'data' => [
                    'account_id' => $id,
                    'balance' => $balance,
                    'as_of_date' => $asOfDate,
                ]
            ]);
        } catch (FinanceException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get account activity (ledger transactions)
     */
    public function getAccountActivity(string $id, Request $request): JsonResponse
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $activity = $this->ledgerRepository->getAccountActivity($id, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $activity
        ]);
    }

    /**
     * Create a new journal entry
     */
    public function createJournalEntry(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entry_date' => 'required|date',
            'description' => 'required|string|max:500',
            'reference' => 'nullable|string|max:100',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|string',
            'lines.*.debit_amount' => 'nullable|numeric|min:0',
            'lines.*.credit_amount' => 'nullable|numeric|min:0',
            'lines.*.description' => 'nullable|string|max:500',
        ]);

        try {
            $entry = $this->financeManager->createJournalEntry(
                $validated['entry_date'],
                $validated['description'],
                $validated['lines'],
                $validated['reference'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $entry->getId(),
                    'entry_number' => $entry->getEntryNumber(),
                    'status' => $entry->getStatus(),
                ]
            ], 201);
        } catch (FinanceException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get a journal entry by ID
     */
    public function getJournalEntry(string $id): JsonResponse
    {
        try {
            $entry = $this->financeManager->getJournalEntry($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $entry->getId(),
                    'entry_number' => $entry->getEntryNumber(),
                    'entry_date' => $entry->getDate()->format('Y-m-d'),
                    'description' => $entry->getDescription(),
                    'reference' => $entry->getReference(),
                    'status' => $entry->getStatus(),
                    'created_by' => $entry->getCreatedBy(),
                    'posted_at' => $entry->getPostedAt()?->format('Y-m-d H:i:s'),
                    'lines' => array_map(fn($line) => [
                        'account_id' => $line->getAccountId(),
                        'debit_amount' => $line->getDebitAmount()->getAmount(),
                        'credit_amount' => $line->getCreditAmount()->getAmount(),
                        'description' => $line->getDescription(),
                    ], $entry->getLines())
                ]
            ]);
        } catch (FinanceException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Post a journal entry
     */
    public function postJournalEntry(string $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period_id' => 'nullable|string',
        ]);

        try {
            $this->financeManager->postJournalEntry($id, $validated['period_id'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Journal entry posted successfully'
            ]);
        } catch (FinanceException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Reverse a journal entry
     * 
     * Creates a reversal entry that swaps debits and credits,
     * publishes events to EventStream for SOX compliance.
     */
    public function reverseJournalEntry(string $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reversal_date' => 'required|date',
            'reason' => 'required|string|max:500',
        ]);

        try {
            $reversalEntry = $this->financeManager->reverseJournalEntry(
                $id,
                new \DateTimeImmutable($validated['reversal_date']),
                $validated['reason']
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'reversal_entry_id' => $reversalEntry->getId(),
                    'reversal_entry_number' => $reversalEntry->getEntryNumber(),
                    'reversal_date' => $validated['reversal_date'],
                    'reason' => $validated['reason'],
                ],
                'message' => 'Journal entry reversed successfully. Events published to EventStream.'
            ]);
        } catch (FinanceException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Delete a draft journal entry
     */
    public function deleteJournalEntry(string $id): JsonResponse
    {
        try {
            $this->financeManager->deleteJournalEntry($id);

            return response()->json([
                'success' => true,
                'message' => 'Journal entry deleted successfully'
            ]);
        } catch (FinanceException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get trial balance
     */
    public function getTrialBalance(Request $request): JsonResponse
    {
        $asOfDate = $request->query('as_of_date');

        $trialBalance = $this->ledgerRepository->getTrialBalance($asOfDate);

        return response()->json([
            'success' => true,
            'data' => $trialBalance
        ]);
    }

    /**
     * Get general ledger
     */
    public function getGeneralLedger(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $accountId = $request->query('account_id');

        $ledger = $this->ledgerRepository->getGeneralLedger($startDate, $endDate, $accountId);

        return response()->json([
            'success' => true,
            'data' => $ledger
        ]);
    }
}
