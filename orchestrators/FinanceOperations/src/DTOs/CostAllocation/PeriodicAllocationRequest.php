<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\CostAllocation;

/**
 * Request DTO for periodic cost allocation.
 *
 * Used to run scheduled cost allocations for a period,
 * processing multiple allocation rules in batch.
 *
 * @since 1.0.0
 */
final readonly class PeriodicAllocationRequest
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $periodId Accounting period for allocation
     * @param array<string> $allocationRuleIds Specific rules to run (empty = all active)
     * @param bool $postToGL Post resulting journal entries to GL
     * @param bool $validateOnly Validate without posting
     */
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public array $allocationRuleIds = [],
        public bool $postToGL = true,
        public bool $validateOnly = false,
    ) {}
}
