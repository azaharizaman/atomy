<?php

declare(strict_types=1);

namespace App\Service\Budget\Adapters;

use Nexus\Budget\Contracts\BudgetTransactionInterface;
use Nexus\Budget\Contracts\BudgetTransactionRepositoryInterface;
use Nexus\Budget\Enums\TransactionType;
use Nexus\Common\ValueObjects\Money;

/**
 * Canary Unsupported Budget Transaction Repository Adapter
 * 
 * This adapter is a placeholder that throws exceptions for all operations
 * because BudgetTransaction persistence is not supported in the canary runtime.
 */
final class CanaryUnsupportedBudgetTransactionRepositoryAdapter implements BudgetTransactionRepositoryInterface
{
    private const string UNSUPPORTED_MESSAGE = 'BudgetTransaction repository operation %s is not supported for canary runtime.';

    public function create(array $data): BudgetTransactionInterface
    {
        throw new \RuntimeException(sprintf(self::UNSUPPORTED_MESSAGE . ' (data count=%d)', __FUNCTION__, count($data)));
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
            self::UNSUPPORTED_MESSAGE . ' (budgetId=%s, amount=%s, accountId=%s, source=%s:%s, line=%d, costCenter=%s, workflow=%s, type=%s)',
            __FUNCTION__,
            $budgetId,
            (string) $amount,
            $accountId,
            $sourceType,
            $sourceId,
            $sourceLineNumber,
            $costCenterId ?? 'null',
            $workflowApprovalId ?? 'null',
            $transactionType?->value ?? 'null'
        ));
    }

    public function releaseCommitment(string $id): void
    {
        throw new \RuntimeException(sprintf(self::UNSUPPORTED_MESSAGE . ' (transactionId=%s)', __FUNCTION__, $id));
    }

    public function recordActual(
        string $budgetId,
        Money $amount,
        string $sourceId,
        TransactionType $transactionType
    ): void {
        throw new \RuntimeException(sprintf(
            self::UNSUPPORTED_MESSAGE . ' (budgetId=%s, amount=%s, sourceId=%s, type=%s)',
            __FUNCTION__,
            $budgetId,
            (string) $amount,
            $sourceId,
            $transactionType->value
        ));
    }

    public function reverseTransaction(string $transactionId, string $reason): void
    {
        throw new \RuntimeException(sprintf(
            self::UNSUPPORTED_MESSAGE . ' (transactionId=%s, reason=%s)',
            __FUNCTION__,
            $transactionId,
            $reason
        ));
    }

    public function findByBudget(string $budgetId): array
    {
        throw new \RuntimeException(sprintf(self::UNSUPPORTED_MESSAGE . ' (budgetId=%s)', __FUNCTION__, $budgetId));
    }

    public function findBySource(string $sourceType, string $sourceId): array
    {
        throw new \RuntimeException(sprintf(self::UNSUPPORTED_MESSAGE . ' (sourceType=%s, sourceId=%s)', __FUNCTION__, $sourceType, $sourceId));
    }

    public function findMatchingCommitment(string $sourceType, string $sourceId, int $sourceLineNumber): ?BudgetTransactionInterface
    {
        throw new \RuntimeException(sprintf(
            self::UNSUPPORTED_MESSAGE . ' (sourceType=%s, sourceId=%s, line=%d)',
            __FUNCTION__,
            $sourceType,
            $sourceId,
            $sourceLineNumber
        ));
    }

    public function sumCommitmentsByAccount(string $accountId, string $periodId): Money
    {
        throw new \RuntimeException(sprintf(self::UNSUPPORTED_MESSAGE . ' (accountId=%s, periodId=%s)', __FUNCTION__, $accountId, $periodId));
    }
}
