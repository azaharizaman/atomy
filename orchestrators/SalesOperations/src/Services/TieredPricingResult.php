<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Services;

final readonly class TieredPricingResult
{
    public function __construct(
        public float $basePrice,
        public array $tiers,
    ) {}

    public function getTierCount(): int
    {
        return count($this->tiers);
    }
}
