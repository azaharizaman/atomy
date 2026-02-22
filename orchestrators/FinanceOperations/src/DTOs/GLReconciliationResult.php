<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Result DTO for GL reconciliation operations.
 */
final readonly class GLReconciliationResult
{
    public function __construct(
        public bool $success,
        public string $periodId,
        public float $subledgerBalance,
        public float $glBalance,
        public float $difference,
        public array $discrepancies = [],
        public ?string $errorMessage = null,
    ) {}
}
