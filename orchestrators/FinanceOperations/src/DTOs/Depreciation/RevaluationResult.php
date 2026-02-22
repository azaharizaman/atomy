<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\Depreciation;

/**
 * Result DTO for asset revaluation operations.
 *
 * Contains the results of an asset revaluation including
 * previous and new values, and the revaluation amount.
 *
 * @since 1.0.0
 */
final readonly class RevaluationResult
{
    /**
     * @param bool $success Whether the revaluation succeeded
     * @param string $assetId Asset identifier
     * @param string $previousValue Previous asset value (as string to avoid float precision issues)
     * @param string $newValue New asset value after revaluation
     * @param string $revaluationAmount Difference between new and previous value
     * @param string|null $journalEntryId ID of the journal entry created
     * @param string|null $error Error message if operation failed
     */
    public function __construct(
        public bool $success,
        public string $assetId,
        public string $previousValue = '0',
        public string $newValue = '0',
        public string $revaluationAmount = '0',
        public ?string $journalEntryId = null,
        public ?string $error = null,
    ) {}
}
