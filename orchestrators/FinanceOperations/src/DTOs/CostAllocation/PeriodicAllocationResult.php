<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\CostAllocation;

/**
 * Result DTO for periodic cost allocation.
 *
 * Contains the results of running periodic cost allocations,
 * including the number of allocations processed and totals.
 *
 * @since 1.0.0
 */
final readonly class PeriodicAllocationResult
{
    /**
     * @param bool $success Whether the allocation succeeded
     * @param string $periodId Accounting period processed
     * @param int $allocationsProcessed Number of allocation rules processed
     * @param string $totalAllocated Total amount allocated (as string to avoid float precision issues)
     * @param array<int, array{ruleId: string, ruleName: string, amount: string, status: string}> $details Detailed results per rule
     * @param string|null $error Error message if operation failed
     */
    public function __construct(
        public bool $success,
        public string $periodId,
        public int $allocationsProcessed = 0,
        public string $totalAllocated = '0',
        public array $details = [],
        public ?string $error = null,
    ) {}
}
