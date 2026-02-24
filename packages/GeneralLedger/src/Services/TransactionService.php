<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Services;

use Nexus\GeneralLedger\Contracts\LedgerAccountQueryInterface;
use Nexus\GeneralLedger\Contracts\LedgerQueryInterface;
use Nexus\GeneralLedger\Contracts\TransactionPersistInterface;
use Nexus\GeneralLedger\Contracts\TransactionQueryInterface;
use Nexus\GeneralLedger\Entities\Ledger;
use Nexus\GeneralLedger\Entities\LedgerAccount;
use Nexus\GeneralLedger\Entities\Transaction;
use Nexus\GeneralLedger\Enums\TransactionType;
use Nexus\GeneralLedger\Exceptions\InvalidPostingException;
use Nexus\GeneralLedger\Exceptions\LedgerNotFoundException;
use Nexus\GeneralLedger\Exceptions\PeriodClosedException;
use Nexus\GeneralLedger\ValueObjects\AccountBalance;
use Nexus\GeneralLedger\ValueObjects\PostingResult;
use Nexus\GeneralLedger\ValueObjects\TransactionDetail;
use Symfony\Component\Uid\Ulid;

/**
 * Transaction Service
 * 
 * Service for posting transactions to the general ledger.
 * Handles validation, balance calculation, and transaction creation.
 */
final readonly class TransactionService
{
    public function __construct(
        private TransactionQueryInterface $queryRepository,
        private TransactionPersistInterface $persistRepository,
        private LedgerQueryInterface $ledgerQuery,
        private LedgerAccountQueryInterface $accountQuery,
        private BalanceCalculationService $balanceService,
    ) {}

    /**
     * Post a single transaction to the GL
     * 
     * @param string $ledgerId Ledger ULID
     * @param TransactionDetail $detail Transaction details
     * @param string $periodId Period ULID
     * @param \DateTimeImmutable $postingDate Posting date
     * @param \DateTimeImmutable $transactionDate Transaction date
     * @return PostingResult Result of the posting
     */
    public function postTransaction(
        string $ledgerId,
        TransactionDetail $detail,
        string $periodId,
        \DateTimeImmutable $postingDate,
        \DateTimeImmutable $transactionDate,
    ): PostingResult {
        try {
            // Validate ledger exists and is active
            $ledger = $this->ledgerQuery->findById($ledgerId);
            if ($ledger === null) {
                return PostingResult::failure(
                    'LEDGER_NOT_FOUND',
                    sprintf('Ledger not found: %s', $ledgerId)
                );
            }

            if (!$ledger->canPostTransactions()) {
                return PostingResult::failure(
                    'LEDGER_INACTIVE',
                    sprintf('Ledger %s is not active for posting', $ledgerId)
                );
            }

            // Validate account exists and allows posting
            $account = $this->accountQuery->findById($detail->ledgerAccountId);
            if ($account === null) {
                return PostingResult::failure(
                    'ACCOUNT_NOT_FOUND',
                    sprintf('Account not found: %s', $detail->ledgerAccountId)
                );
            }

            if ($account->ledgerId !== $ledgerId) {
                return PostingResult::failure(
                    'ACCOUNT_MISMATCH',
                    sprintf('Account %s does not belong to ledger %s', $detail->ledgerAccountId, $ledgerId)
                );
            }

            if (!$account->canPostTransactions()) {
                return PostingResult::failure(
                    'ACCOUNT_INACTIVE',
                    sprintf('Account %s does not allow posting', $detail->ledgerAccountId)
                );
            }

            // Get current balance and calculate new running balance
            $currentBalance = $this->balanceService->getAccountBalance($detail->ledgerAccountId);
            $newBalance = $this->balanceService->calculateNewBalance(
                $currentBalance,
                $detail->amount,
                $detail->type,
                $account->balanceType
            );

            // Create the transaction
            $transaction = Transaction::create(
                id: (string) Ulid::generate(),
                ledgerAccountId: $detail->ledgerAccountId,
                journalEntryLineId: $detail->journalEntryLineId ?? (string) Ulid::generate(),
                journalEntryId: (string) Ulid::generate(),
                type: $detail->type,
                amount: $detail->amount,
                runningBalance: $newBalance,
                periodId: $periodId,
                postingDate: $postingDate,
                transactionDate: $transactionDate,
                description: $detail->description,
                reference: $detail->reference,
            );

            // Save the transaction
            $this->persistRepository->save($transaction);

            return PostingResult::success($transaction, [
                'ledger_id' => $ledgerId,
                'period_id' => $periodId,
                'previous_balance' => $currentBalance->toArray(),
                'new_balance' => $newBalance->toArray(),
            ]);

        } catch (\Exception $e) {
            return PostingResult::failure(
                'POSTING_ERROR',
                $e->getMessage()
            );
        }
    }

    /**
     * Post multiple transactions in a batch
     * 
     * @param string $ledgerId Ledger ULID
     * @param array<TransactionDetail> $details Transaction details
     * @param string $periodId Period ULID
     * @param \DateTimeImmutable $postingDate Posting date
     * @param \DateTimeImmutable $transactionDate Transaction date
     * @return PostingResult Result of batch posting
     */
    public function postBatch(
        string $ledgerId,
        array $details,
        string $periodId,
        \DateTimeImmutable $postingDate,
        \DateTimeImmutable $transactionDate,
    ): PostingResult {
        $successfulTransactions = [];
        $failedItems = [];

        foreach ($details as $index => $detail) {
            $result = $this->postTransaction(
                $ledgerId,
                $detail,
                $periodId,
                $postingDate,
                $transactionDate,
            );

            if ($result->isSuccessful()) {
                $successfulTransactions[] = $result->getTransactionId();
            } else {
                $failedItems[] = [
                    'index' => $index,
                    'error_code' => $result->errorCode,
                    'error_message' => $result->errorMessage,
                ];
            }
        }

        if (empty($failedItems)) {
            return PostingResult::batchSuccess($successfulTransactions);
        }

        if (!empty($successfulTransactions)) {
            return PostingResult::batchPartialSuccess(
                $successfulTransactions,
                $failedItems
            );
        }

        return PostingResult::failure(
            'BATCH_ALL_FAILED',
            'All transactions failed to post'
        );
    }

    /**
     * Reverse a transaction
     * 
     * @param string $transactionId Transaction ULID
     * @param string $reason Reason for reversal
     * @param string $periodId Reversal period ULID
     * @return PostingResult Result of reversal
     */
    public function reverseTransaction(
        string $transactionId,
        string $reason,
        string $periodId,
    ): PostingResult {
        try {
            $originalTransaction = $this->queryRepository->findById($transactionId);
            
            if ($originalTransaction === null) {
                return PostingResult::failure(
                    'TRANSACTION_NOT_FOUND',
                    sprintf('Transaction not found: %s', $transactionId)
                );
            }

            if (!$originalTransaction->canReverse()) {
                return PostingResult::failure(
                    'ALREADY_REVERSED',
                    sprintf('Transaction %s has already been reversed', $transactionId)
                );
            }

            $account = $this->accountQuery->findById($originalTransaction->ledgerAccountId);
            if ($account === null) {
                return PostingResult::failure(
                    'ACCOUNT_NOT_FOUND',
                    sprintf('Account not found: %s', $originalTransaction->ledgerAccountId)
                );
            }

            // Create reversal with opposite type
            $reversalType = $originalTransaction->type->opposite();
            $detail = new TransactionDetail(
                ledgerAccountId: $originalTransaction->ledgerAccountId,
                type: $reversalType,
                amount: $originalTransaction->amount,
                journalEntryLineId: $originalTransaction->journalEntryLineId,
                description: 'Reversal: ' . ($reason ?: $originalTransaction->id),
                reference: 'Reversal of ' . $originalTransaction->id,
            );

            $result = $this->postTransaction(
                $account->ledgerId,
                $detail,
                $periodId,
                new \DateTimeImmutable(),
                $originalTransaction->transactionDate,
            );

            if ($result->isSuccessful() && $result->transaction !== null) {
                // Mark original as reversed
                $this->persistRepository->markAsReversed(
                    $transactionId,
                    $result->transaction->id
                );
            }

            return $result;

        } catch (\Exception $e) {
            return PostingResult::failure(
                'REVERSAL_ERROR',
                $e->getMessage()
            );
        }
    }

    /**
     * Get account balance
     * 
     * @param string $ledgerAccountId LedgerAccount ULID
     * @param \DateTimeImmutable|null $asOfDate Balance as of date
     * @return AccountBalance Current balance
     */
    public function getAccountBalance(
        string $ledgerAccountId,
        ?\DateTimeImmutable $asOfDate = null,
    ): AccountBalance {
        $asOfDate ??= new \DateTimeImmutable();
        return $this->queryRepository->getAccountBalance($ledgerAccountId, $asOfDate);
    }

    /**
     * Get account transactions
     * 
     * @param string $ledgerAccountId LedgerAccount ULID
     * @param \DateTimeImmutable|null $fromDate From date
     * @param \DateTimeImmutable|null $toDate To date
     * @return array<Transaction> Transactions
     */
    public function getAccountTransactions(
        string $ledgerAccountId,
        ?\DateTimeImmutable $fromDate = null,
        ?\DateTimeImmutable $toDate = null,
    ): array {
        if ($fromDate !== null && $toDate !== null) {
            return $this->queryRepository->findByDateRange($ledgerAccountId, $fromDate, $toDate);
        }

        return $this->queryRepository->findByAccount($ledgerAccountId);
    }
}
