<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Result DTO for revaluation operations.
 */
final readonly class RevaluationResult
{
    public function __construct(
        public bool $success,
        public string $assetId,
        public float $previousValue,
        public float $newValue,
        public float $adjustment,
        public ?string $errorMessage = null,
    ) {}
}
