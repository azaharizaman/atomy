<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\CostAllocation;

use Nexus\FinanceOperations\Enums\AllocationMethod;

/**
 * Request DTO for cost allocation operations.
 *
 * Used to allocate costs from a source cost pool to
 * target cost centers using specified allocation methods.
 *
 * @since 1.0.0
 */
final readonly class CostAllocationRequest
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $periodId Accounting period for allocation
     * @param string $sourceCostPoolId Source cost pool to allocate from
     * @param array<string> $targetCostCenterIds Target cost center IDs
     * @param AllocationMethod $allocationMethod Allocation method enum
     * @param array<string, mixed> $options Additional allocation options
     */
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public string $sourceCostPoolId,
        public array $targetCostCenterIds = [],
        public AllocationMethod $allocationMethod = AllocationMethod::PROPORTIONAL,
        public array $options = [],
    ) {}
}
