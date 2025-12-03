<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\DTOs;

/**
 * Result DTO for hiring workflow.
 */
final readonly class HiringResult
{
    public function __construct(
        public bool $success,
        public ?string $employeeId = null,
        public ?string $userId = null,
        public ?string $message = null,
        public ?array $issues = null,
    ) {}
}
