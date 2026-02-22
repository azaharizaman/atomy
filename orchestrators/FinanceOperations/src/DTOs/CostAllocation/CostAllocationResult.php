<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\CostAllocation;

/**
 * Result DTO for cost allocation operations.
 *
 * Contains the results of allocating costs from a source pool
 * to target cost centers, including generated journal entries.
 *
 * @since 1.0.0
 */
final readonly class CostAllocationResult
{
    /**
     * @param bool $success Whether the allocation succeeded
     * @param string $allocationId Unique identifier for this allocation
     * @param string $totalAllocated Total amount allocated (as string to avoid float precision issues)
     * @param array<int, array{costCenterId: string, costCenterName: string, amount: string, percentage: float}> $allocations Detailed allocation breakdown
     * @param array<string> $journalEntryIds IDs of generated journal entries
     * @param string|null $error Error message if operation failed
     */
    public function __construct(
        public bool $success,
        public string $allocationId,
        public string $totalAllocated = '0',
        public array $allocations = [],
        public array $journalEntries = [],
        public ?string $error = null,
    ) {}
}
