<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

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
     * Find transactions by budget
     * 
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
     * @return \Nexus\Uom\ValueObjects\Money
     */
    public function sumCommitmentsByAccount(string $accountId, string $periodId): \Nexus\Uom\ValueObjects\Money;
}
