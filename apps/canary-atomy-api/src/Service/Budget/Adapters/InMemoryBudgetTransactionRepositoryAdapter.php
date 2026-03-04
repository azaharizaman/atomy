<?php

declare(strict_types=1);

namespace App\Service\Budget\Adapters;

use Nexus\Budget\Contracts\BudgetTransactionInterface;
use Nexus\Budget\Contracts\BudgetTransactionRepositoryInterface;
use Nexus\Budget\Enums\TransactionType;
use Nexus\Common\ValueObjects\Money;

final class InMemoryBudgetTransactionRepositoryAdapter implements BudgetTransactionRepositoryInterface
{
    public function create(array $data): BudgetTransactionInterface
    {
        throw new \RuntimeException('BudgetTransaction repository adapter is not fully implemented for canary runtime.');
    }

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
    ): void {
        throw new \RuntimeException(sprintf(
            'recordCommitment is not implemented for canary runtime (budgetId=%s, source=%s:%s, line=%d, amount=%s).',
            $budgetId,
            $sourceType,
            $sourceId,
            $sourceLineNumber,
            (string) $amount
        ));
    }

    public function releaseCommitment(string $id): void
    {
        throw new \RuntimeException(sprintf(
            'releaseCommitment is not implemented for canary runtime (transactionId=%s).',
            $id
        ));
    }

    public function recordActual(
        string $budgetId,
        Money $amount,
        string $sourceId,
        TransactionType $transactionType
    ): void {
        throw new \RuntimeException(sprintf(
            'recordActual is not implemented for canary runtime (budgetId=%s, sourceId=%s, type=%s, amount=%s).',
            $budgetId,
            $sourceId,
            $transactionType->value,
            (string) $amount
        ));
    }

    public function reverseTransaction(string $transactionId, string $reason): void
    {
        throw new \RuntimeException(sprintf(
            'reverseTransaction is not implemented for canary runtime (transactionId=%s, reason=%s).',
            $transactionId,
            $reason
        ));
    }

    public function findByBudget(string $budgetId): array
    {
        return [];
    }

    public function findBySource(string $sourceType, string $sourceId): array
    {
        return [];
    }

    public function findMatchingCommitment(string $sourceType, string $sourceId, int $sourceLineNumber): ?BudgetTransactionInterface
    {
        return null;
    }

    public function sumCommitmentsByAccount(string $accountId, string $periodId): Money
    {
        throw new \RuntimeException('BudgetTransaction repository adapter is not fully implemented for canary runtime.');
    }
}
