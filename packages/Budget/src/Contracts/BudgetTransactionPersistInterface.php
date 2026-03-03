<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\Budget\Enums\TransactionType;

/**
 * Budget Transaction Persistence contract
 * 
 * Provides write access for budget transaction records.
 */
interface BudgetTransactionPersistInterface
{
    /**
     * Create new budget transaction
     * 
     * @param array<string, mixed> $data Transaction data
     * @return BudgetTransactionInterface
     */
    public function create(array $data): BudgetTransactionInterface;

    /**
     * Record budget commitment
     * 
     * @param string $budgetId Budget identifier
     * @param Money $amount Commitment amount
     * @param string $accountId GL account identifier
     * @param string $sourceType Source type
     * @param string $sourceId Source identifier
     * @param int $sourceLineNumber Source line number
     * @param string|null $costCenterId Cost center identifier
     * @param string|null $workflowApprovalId Workflow approval identifier
     * @param TransactionType|null $transactionType Transaction type
     * @return void
     */
     public function recordCommitment(
        string $budgetId,
        Money $amount,
        string $accountId,
        string $sourceType,
        string $sourceId,
        int $sourceLineNumber,
        ?string $costCenterId = null,
        ?string $workflowApprovalId = null,
        ?TransactionType $transactionType = null
    ): void;

    /**
     * Release committed amount
     * 
     * @param string $id Transaction identifier
     * @return void
     */
    public function releaseCommitment(string $id): void;

    /**
     * Record actual spending
     * 
     * @param string $budgetId Budget identifier
     * @param Money $amount Actual amount
     * @param string $sourceId Source identifier
     * @param TransactionType $transactionType Transaction type
     * @return void
     */
    public function recordActual(
        string $budgetId,
        Money $amount,
        string $sourceId,
        TransactionType $transactionType
    ): void;

    /**
     * Reverse a transaction
     * 
     * @param string $transactionId Transaction identifier to reverse
     * @param string $reason Reversal reason
     * @return void
     */
    public function reverseTransaction(string $transactionId, string $reason): void;
}
