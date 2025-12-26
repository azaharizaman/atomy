<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Services;

use Nexus\PaymentBank\Contracts\BankStatementQueryInterface;
use Nexus\PaymentBank\Contracts\BankTransactionQueryInterface;
use Nexus\PaymentBank\DTOs\InternalTransaction;
use Nexus\PaymentBank\Exceptions\BankStatementNotFoundException;
use Psr\Log\LoggerInterface;

final readonly class BankReconciliationManager
{
    /** @var float Tolerance for amount matching */
    private const float AMOUNT_TOLERANCE = 0.001;

    public function __construct(
        private BankTransactionQueryInterface $transactionQuery,
        private BankStatementQueryInterface $statementQuery,
        private LoggerInterface $logger
    ) {}

    /**
     * @param string $statementId
     * @param array<InternalTransaction> $internalTransactions
     * @return array
     */
    public function reconcile(string $statementId, array $internalTransactions = []): array
    {
        $statement = $this->statementQuery->findById($statementId);

        if (!$statement) {
            throw new BankStatementNotFoundException($statementId);
        }

        $bankTransactions = $this->transactionQuery->findByConnectionAndDateRange(
            $statement->getConnectionId(),
            $statement->getStartDate(),
            $statement->getEndDate()
        );

        $matched = [];
        $unmatchedBank = [];
        
        // Working copy of internal transactions to track which are matched
        $remainingInternalTxns = $internalTransactions;

        foreach ($bankTransactions as $bankTxn) {
            $matchFound = false;
            foreach ($remainingInternalTxns as $key => $internalTxn) {
                // Match by Amount and Date
                $amountMatch = abs($bankTxn->getAmount() - $internalTxn->getAmount()) < self::AMOUNT_TOLERANCE;
                
                $bankDate = $bankTxn->getDate()->format('Y-m-d');
                $internalDate = $internalTxn->getDate();
                $dateMatch = $bankDate === $internalDate;

                if ($amountMatch && $dateMatch) {
                    $matched[] = [
                        'bank_transaction' => $bankTxn,
                        'internal_transaction' => $internalTxn
                    ];
                    unset($remainingInternalTxns[$key]);
                    $matchFound = true;
                    break; // Move to next bank transaction
                }
            }

            if (!$matchFound) {
                $unmatchedBank[] = $bankTxn;
            }
        }

        $unmatchedInternal = array_values($remainingInternalTxns);

        $this->logger->info("Reconciled statement $statementId: " . count($matched) . " matched.");

        return [
            'matched' => $matched,
            'unmatched_bank' => $unmatchedBank,
            'unmatched_internal' => $unmatchedInternal
        ];
    }
}
