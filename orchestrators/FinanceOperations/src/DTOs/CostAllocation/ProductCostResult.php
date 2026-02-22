<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\CostAllocation;

/**
 * Result DTO for product cost calculation.
 *
 * Contains the calculated product cost broken down by
 * material, labor, and overhead components.
 *
 * @since 1.0.0
 */
final readonly class ProductCostResult
{
    /**
     * @param bool $success Whether the calculation succeeded
     * @param string $productId Product identifier
     * @param string $materialCost Material cost component (as string to avoid float precision issues)
     * @param string $laborCost Labor cost component
     * @param string $overheadCost Overhead cost component
     * @param string $totalCost Total product cost
     * @param array<int, array{component: string, quantity: string, unitCost: string, totalCost: string}> $costBreakdown Detailed cost breakdown
     * @param string|null $error Error message if operation failed
     */
    public function __construct(
        public bool $success,
        public string $productId,
        public string $materialCost = '0',
        public string $laborCost = '0',
        public string $overheadCost = '0',
        public string $totalCost = '0',
        public array $costBreakdown = [],
        public ?string $error = null,
    ) {}
}
