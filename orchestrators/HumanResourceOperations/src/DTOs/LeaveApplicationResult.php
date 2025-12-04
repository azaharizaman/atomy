<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\DTOs;

/**
 * Result DTO for leave application.
 */
final readonly class LeaveApplicationResult
{
    public function __construct(
        public bool $success,
        public ?string $leaveRequestId = null,
        public ?float $newBalance = null,
        public ?string $message = null,
        public ?array $issues = null,
    ) {}
}
