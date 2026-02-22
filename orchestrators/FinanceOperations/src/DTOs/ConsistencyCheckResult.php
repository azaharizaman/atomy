<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Result DTO for consistency check operations.
 */
final readonly class ConsistencyCheckResult
{
    public function __construct(
        public bool $success,
        public bool $isConsistent,
        public array $issues = [],
        public ?string $errorMessage = null,
    ) {}
}
