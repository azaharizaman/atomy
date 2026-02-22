<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Result DTO for cost allocation operations.
 */
final readonly class CostAllocationResult
{
    public function __construct(
        public bool $success,
        public string $periodId,
        public float $totalAllocated,
        public array $allocations = [],
        public ?string $errorMessage = null,
    ) {}
}
