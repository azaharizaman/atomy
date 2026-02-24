<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Services;

use Nexus\GeneralLedger\Contracts\DatabaseTransactionInterface;
use Nexus\GeneralLedger\Contracts\IdGeneratorInterface;
use Nexus\GeneralLedger\Contracts\LedgerAccountQueryInterface;
use Nexus\GeneralLedger\Contracts\LedgerQueryInterface;
use Nexus\GeneralLedger\Contracts\TransactionPersistInterface;
use Nexus\GeneralLedger\Contracts\TransactionQueryInterface;
use Nexus\GeneralLedger\Contracts\BalanceCalculationInterface;
use Nexus\GeneralLedger\Entities\Ledger;
use Nexus\GeneralLedger\Entities\LedgerAccount;
use Nexus\GeneralLedger\Entities\Transaction;
use Nexus\GeneralLedger\Enums\TransactionType;
use Nexus\GeneralLedger\Exceptions\GeneralLedgerException;
use Nexus\GeneralLedger\Exceptions\InvalidPostingException;
use Nexus\GeneralLedger\Exceptions\LedgerNotFoundException;
use Nexus\GeneralLedger\Exceptions\PeriodClosedException;
use Nexus\GeneralLedger\Exceptions\BatchPostingException;
use Nexus\GeneralLedger\ValueObjects\AccountBalance;
use Nexus\GeneralLedger\ValueObjects\PostingResult;
use Nexus\GeneralLedger\ValueObjects\TransactionDetail;

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
        private BalanceCalculationInterface $balanceService,
        private IdGeneratorInterface $idGenerator,
        private DatabaseTransactionInterface $db,
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
            // Validate journalEntryLineId is present
            if ($detail->journalEntryLineId === null) {
                return PostingResult::failure(
                    'MISSING_LINE_ID',
                    'Source journal entry line ID is required'
                );
            }

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
                id: $this->idGenerator->generate(),
                ledgerAccountId: $detail->ledgerAccountId,
                journalEntryLineId: $detail->journalEntryLineId,
                journalEntryId: $detail->journalEntryId,
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

        } catch (GeneralLedgerException $e) {
            // Re-throw domain exceptions
            throw $e;
        } catch (\Throwable $e) {
            // Wrap unexpected infrastructure exceptions
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
        try {
            return $this->db->transactional(function() use ($ledgerId, $details, $periodId, $postingDate, $transactionDate) {
                $successfulTransactions = [];

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
                        // In a atomic batch, if one fails, we roll back everything
                        throw new BatchPostingException(
                            sprintf('Batch item %d failed: %s', $index, $result->errorMessage)
                        );
                    }
                }

                return PostingResult::batchSuccess($successfulTransactions);
            });
        } catch (\Throwable $e) {
            return PostingResult::failure(
                'BATCH_FAILED',
                $e->getMessage()
            );
        }
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
            return $this->db->transactional(function() use ($transactionId, $reason, $periodId) {
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

                // Get current balance and calculate new running balance for the reversal
                $currentBalance = $this->balanceService->getAccountBalance($originalTransaction->ledgerAccountId);
                
                // Reversal amount is same as original, but effectively opposite sign
                // We use the original's opposite type for calculation
                $newBalance = $this->balanceService->calculateNewBalance(
                    $currentBalance,
                    $originalTransaction->amount,
                    $originalTransaction->type->opposite(),
                    $account->balanceType
                );

                // Use the Entity's reverse method to ensure consistency
                [$reversal, $originalWithRef] = $originalTransaction->reverse(
                    $this->idGenerator->generate(),
                    $periodId,
                    $newBalance,
                    $reason
                );

                // Update original transaction to mark it as reversed
                $this->persistRepository->save($originalWithRef);

                // Save the reversal transaction
                $this->persistRepository->save($reversal);

                return PostingResult::success($reversal, [
                    'original_transaction_id' => $transactionId,
                    'period_id' => $periodId,
                    'reversal_reason' => $reason
                ]);
            });

        } catch (GeneralLedgerException $e) {
            throw $e;
        } catch (\Throwable $e) {
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
