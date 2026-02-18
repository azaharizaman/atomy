<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Services;

final readonly class CommissionResult
{
    public function __construct(
        public string $salespersonId,
        public string $orderId,
        public string $paymentId,
        public string $basis,
        public float $rate,
        public float $revenueAmount,
        public float $costAmount,
        public float $grossProfit,
        public float $commissionableAmount,
        public float $commissionAmount,
        public string $currencyCode,
        public bool $isOverride = false,
        public ?string $overrideReason = null,
    ) {}

    public function effectiveRate(): float
    {
        if ($this->revenueAmount <= 0) {
            return 0.0;
        }

        return ($this->commissionAmount / $this->revenueAmount) * 100;
    }
}
