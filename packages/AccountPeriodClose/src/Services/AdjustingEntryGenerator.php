<?php

declare(strict_types=1);

namespace Nexus\AccountPeriodClose\Services;

use Nexus\AccountPeriodClose\ValueObjects\ClosingEntrySpec;

/**
 * Pure logic for generating period-end adjusting entries.
 */
final readonly class AdjustingEntryGenerator
{
    /**
     * Generate prepaid expense adjustment.
     *
     * @param string $prepaidAccountCode
     * @param string $expenseAccountCode
     * @param float $amountToRecognize
     * @param \DateTimeImmutable $entryDate
     * @return ClosingEntrySpec
     */
    public function generatePrepaidAdjustment(
        string $prepaidAccountCode,
        string $expenseAccountCode,
        float $amountToRecognize,
        \DateTimeImmutable $entryDate
    ): ClosingEntrySpec {
        return new ClosingEntrySpec(
            description: "Recognize prepaid expense",
            debitAccountCode: $expenseAccountCode,
            creditAccountCode: $prepaidAccountCode,
            amount: $amountToRecognize,
            entryDate: $entryDate,
            reference: 'ADJ-PREPAID'
        );
    }

    /**
     * Generate accrued expense adjustment.
     *
     * @param string $expenseAccountCode
     * @param string $liabilityAccountCode
     * @param float $amountToAccrue
     * @param \DateTimeImmutable $entryDate
     * @return ClosingEntrySpec
     */
    public function generateAccruedExpense(
        string $expenseAccountCode,
        string $liabilityAccountCode,
        float $amountToAccrue,
        \DateTimeImmutable $entryDate
    ): ClosingEntrySpec {
        return new ClosingEntrySpec(
            description: "Accrue expense",
            debitAccountCode: $expenseAccountCode,
            creditAccountCode: $liabilityAccountCode,
            amount: $amountToAccrue,
            entryDate: $entryDate,
            reference: 'ADJ-ACCRUAL'
        );
    }

    /**
     * Generate depreciation adjustment.
     *
     * @param string $depreciationExpenseCode
     * @param string $accumulatedDepreciationCode
     * @param float $depreciationAmount
     * @param \DateTimeImmutable $entryDate
     * @return ClosingEntrySpec
     */
    public function generateDepreciation(
        string $depreciationExpenseCode,
        string $accumulatedDepreciationCode,
        float $depreciationAmount,
        \DateTimeImmutable $entryDate
    ): ClosingEntrySpec {
        return new ClosingEntrySpec(
            description: "Record depreciation",
            debitAccountCode: $depreciationExpenseCode,
            creditAccountCode: $accumulatedDepreciationCode,
            amount: $depreciationAmount,
            entryDate: $entryDate,
            reference: 'ADJ-DEPR'
        );
    }

    /**
     * Generate unearned revenue adjustment.
     *
     * @param string $unearnedRevenueCode
     * @param string $revenueAccountCode
     * @param float $amountEarned
     * @param \DateTimeImmutable $entryDate
     * @return ClosingEntrySpec
     */
    public function generateUnearnedRevenueAdjustment(
        string $unearnedRevenueCode,
        string $revenueAccountCode,
        float $amountEarned,
        \DateTimeImmutable $entryDate
    ): ClosingEntrySpec {
        return new ClosingEntrySpec(
            description: "Recognize unearned revenue",
            debitAccountCode: $unearnedRevenueCode,
            creditAccountCode: $revenueAccountCode,
            amount: $amountEarned,
            entryDate: $entryDate,
            reference: 'ADJ-REVENUE'
        );
    }
}
