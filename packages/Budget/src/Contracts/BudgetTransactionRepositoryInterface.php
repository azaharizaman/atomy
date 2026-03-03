<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

use Nexus\Common\ValueObjects\Money;

/**
 * Budget Transaction Repository contract
 * 
 * Provides data access for budget transaction records.
 */
interface BudgetTransactionRepositoryInterface
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
     * @param \Nexus\Budget\Enums\TransactionType $transactionType Transaction type
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
        ?\Nexus\Budget\Enums\TransactionType $transactionType = null
    ): void;

    /**
     * Release committed amount
     * 
     * @param string $id Transaction identifier
     * @return void
     */
    public function releaseCommitment(string $id): void;

    /**
     * Find transactions by budget
    ...
     * @param string $budgetId Budget identifier
     * @return array<BudgetTransactionInterface>
     */
    public function findByBudget(string $budgetId): array;

    /**
     * Find transactions by source
     * 
     * @param string $sourceType Source type
     * @param string $sourceId Source identifier
     * @return array<BudgetTransactionInterface>
     */
    public function findBySource(string $sourceType, string $sourceId): array;

    /**
     * Find matching commitment transaction
     * 
     * @param string $sourceType Source type
     * @param string $sourceId Source identifier
     * @param int $sourceLineNumber Line number
     * @return BudgetTransactionInterface|null
     */
    public function findMatchingCommitment(
        string $sourceType,
        string $sourceId,
        int $sourceLineNumber
    ): ?BudgetTransactionInterface;

    /**
     * Sum commitments by account and period
     * 
     * @param string $accountId Account identifier
     * @param string $periodId Period identifier
     * @return Money
     */
    public function sumCommitmentsByAccount(string $accountId, string $periodId): Money;
}
