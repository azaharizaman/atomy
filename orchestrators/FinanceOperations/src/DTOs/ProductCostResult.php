<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Result DTO for product cost operations.
 */
final readonly class ProductCostResult
{
    public function __construct(
        public bool $success,
        public string $productId,
        public float $totalCost,
        public array $costBreakdown = [],
        public ?string $errorMessage = null,
    ) {}
}
