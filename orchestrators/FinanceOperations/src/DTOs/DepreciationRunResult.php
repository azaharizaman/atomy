<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Result DTO for depreciation run operations.
 */
final readonly class DepreciationRunResult
{
    public function __construct(
        public bool $success,
        public string $periodId,
        public int $assetCount,
        public float $totalDepreciation,
        public array $depreciationEntries = [],
        public ?string $errorMessage = null,
    ) {}
}
