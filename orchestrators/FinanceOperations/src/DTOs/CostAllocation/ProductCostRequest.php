<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\CostAllocation;

/**
 * Request DTO for product cost calculation.
 *
 * Used to calculate the total cost of a product including
 * material, labor, and overhead components.
 *
 * @since 1.0.0
 */
final readonly class ProductCostRequest
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $productId Product to calculate cost for
     * @param string|null $periodId Accounting period (null for current)
     * @param bool $includeBOM Include bill of materials costs
     * @param bool $includeOverhead Include overhead allocation
     */
    public function __construct(
        public string $tenantId,
        public string $productId,
        public ?string $periodId = null,
        public bool $includeBOM = true,
        public bool $includeOverhead = true,
    ) {}
}
