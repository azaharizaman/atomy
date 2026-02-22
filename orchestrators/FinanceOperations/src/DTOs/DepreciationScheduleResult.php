<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Result DTO for depreciation schedule operations.
 */
final readonly class DepreciationScheduleResult
{
    public function __construct(
        public bool $success,
        public string $assetId,
        public array $schedule = [],
        public ?string $errorMessage = null,
    ) {}
}
