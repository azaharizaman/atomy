<?php

declare(strict_types=1);

namespace Nexus\FinancialRatios\ValueObjects;

/**
 * Input data for ratio calculations.
 */
final readonly class RatioInput
{
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public float $currentAssets,
        public float $currentLiabilities,
        public float $totalAssets,
        public float $totalLiabilities,
        public float $totalEquity,
        public float $inventory,
        public float $accountsReceivable,
        public float $accountsPayable,
        public float $cash,
        public float $revenue,
        public float $costOfGoodsSold,
        public float $grossProfit,
        public float $operatingIncome,
        public float $netIncome,
        public float $interestExpense,
        public float $depreciation,
        public float $amortization,
        public float $operatingCashFlow,
        public float $capitalExpenditures,
        public float $totalDebt,
        public float $longTermDebt,
        public float $shortTermDebt,
        public ?\DateTimeImmutable $periodStartDate = null,
        public ?\DateTimeImmutable $periodEndDate = null,
    ) {}

    /**
     * Calculate working capital.
     */
    public function getWorkingCapital(): float
    {
        return $this->currentAssets - $this->currentLiabilities;
    }

    /**
     * Calculate quick assets (current assets minus inventory).
     */
    public function getQuickAssets(): float
    {
        return $this->currentAssets - $this->inventory;
    }

    /**
     * Calculate EBITDA.
     */
    public function getEbitda(): float
    {
        return $this->operatingIncome + $this->depreciation + $this->amortization;
    }

    /**
     * Calculate EBIT.
     */
    public function getEbit(): float
    {
        return $this->netIncome + $this->interestExpense;
    }
}
