<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\Financial;

use Nexus\Common\ValueObjects\Money;

/**
 * Event dispatched when discount optimization has been completed.
 */
final readonly class DiscountOptimizationCompletedEvent
{
    public function __construct(
        public string $tenantId,
        public int $selectedCount,
        public int $excludedCount,
        public Money $totalInvestment,
        public Money $totalSavings,
        public float $portfolioRoi,
        public Money $availableCash,
        public array $constraints = [],
    ) {}
}
