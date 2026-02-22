<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Request DTO for cost allocation operations.
 */
final readonly class CostAllocationRequest
{
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public string $sourceCostPoolId,
        public array $targetCostCenterIds,
        public string $allocationMethod,
        public array $options = [],
    ) {}
}
