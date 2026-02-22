<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\GLPosting;

/**
 * Result DTO for GL consistency check operations.
 *
 * Contains results of consistency checks between subledgers
 * and general ledger control accounts.
 *
 * @since 1.0.0
 */
final readonly class ConsistencyCheckResult
{
    /**
     * @param bool $success Whether the check operation succeeded
     * @param array<string, array{subledgerType: string, consistent: bool, subledgerBalance: string, glBalance: string, variance: string}> $checks Results per subledger type
     * @param bool $allConsistent Whether all subledgers are consistent with GL
     * @param array<int, array{subledgerType: string, type: string, description: string, amount: string}> $inconsistencies List of inconsistencies found
     * @param string|null $error Error message if operation failed
     */
    public function __construct(
        public bool $success,
        public array $checks = [],
        public bool $allConsistent = true,
        public array $inconsistencies = [],
        public ?string $error = null,
    ) {}
}
