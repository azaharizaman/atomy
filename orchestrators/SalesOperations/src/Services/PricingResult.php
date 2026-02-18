<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Services;

final readonly class PricingResult
{
    public function __construct(
        public string $productId,
        public string $customerId,
        public float $listPrice,
        public array $discounts,
        public float $totalDiscountPercent,
        public float $discountAmount,
        public float $finalPrice,
        public string $currencyCode,
        public float $quantity,
        public float $lineTotal,
    ) {}

    public function hasDiscount(): bool
    {
        return $this->totalDiscountPercent > 0;
    }

    public function savingsPercent(): float
    {
        return $this->totalDiscountPercent;
    }
}
