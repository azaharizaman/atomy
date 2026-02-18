<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Services;

final readonly class PricingInput
{
    public function __construct(
        public string $productId,
        public string $customerId,
        public float $listPrice,
        public float $quantity,
        public string $currencyCode = 'MYR',
        public ?float $customerDiscountPercent = null,
        public ?float $promoDiscountPercent = null,
        public ?string $pricingGroupId = null,
    ) {}
}
