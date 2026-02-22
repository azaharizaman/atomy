<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Result DTO for periodic allocation operations.
 */
final readonly class PeriodicAllocationResult
{
    public function __construct(
        public bool $success,
        public string $periodId,
        public array $allocations = [],
        public ?string $errorMessage = null,
    ) {}
}
