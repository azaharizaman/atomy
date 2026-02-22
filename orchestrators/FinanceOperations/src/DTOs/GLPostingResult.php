<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs;

/**
 * Result DTO for GL posting operations.
 */
final readonly class GLPostingResult
{
    public function __construct(
        public bool $success,
        public string $periodId,
        public int $entryCount,
        public float $totalAmount,
        public array $journalEntryIds = [],
        public ?string $errorMessage = null,
    ) {}
}
