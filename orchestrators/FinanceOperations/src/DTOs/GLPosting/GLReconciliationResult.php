<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\GLPosting;

/**
 * Result DTO for general ledger reconciliation.
 *
 * Contains reconciliation results comparing subledger
 * balances with GL control account balances.
 *
 * @since 1.0.0
 */
final readonly class GLReconciliationResult
{
    /**
     * @param bool $success Whether the reconciliation succeeded
     * @param string $subledgerType Subledger type reconciled
     * @param string $subledgerBalance Subledger balance (as string to avoid float precision issues)
     * @param string $glBalance GL control account balance
     * @param string $variance Difference between subledger and GL
     * @param array<int, array{type: string, reference: string, amount: string, description: string}> $discrepancies List of discrepancies found
     * @param string|null $error Error message if operation failed
     */
    public function __construct(
        public bool $success,
        public string $subledgerType,
        public string $subledgerBalance = '0',
        public string $glBalance = '0',
        public string $variance = '0',
        public array $discrepancies = [],
        public ?string $error = null,
    ) {}
}
