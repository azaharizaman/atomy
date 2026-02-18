<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Services;

final readonly class CommissionSummary
{
    public function __construct(
        public string $salespersonId,
        public float $totalRevenue,
        public float $totalCost,
        public float $totalGrossProfit,
        public float $totalCommission,
        public int $transactionCount,
        public string $currencyCode,
    ) {}

    public function averageCommission(): float
    {
        if ($this->transactionCount === 0) {
            return 0.0;
        }

        return $this->totalCommission / $this->transactionCount;
    }

    public function grossMarginPercent(): float
    {
        if ($this->totalRevenue <= 0) {
            return 0.0;
        }

        return ($this->totalGrossProfit / $this->totalRevenue) * 100;
    }

    public function effectiveCommissionRate(): float
    {
        if ($this->totalRevenue <= 0) {
            return 0.0;
        }

        return ($this->totalCommission / $this->totalRevenue) * 100;
    }
}
