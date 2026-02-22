<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\DTOs\GLPosting;

/**
 * Result DTO for general ledger posting operations.
 *
 * Contains the results of posting subledger transactions
 * to the general ledger.
 *
 * @since 1.0.0
 */
final readonly class GLPostingResult
{
    /**
     * @param bool $success Whether the posting succeeded
     * @param string $postingId Unique identifier for this posting
     * @param string $subledgerType Subledger type that was posted
     * @param int $entriesPosted Number of entries posted
     * @param array<string> $journalEntryIds IDs of created journal entries
     * @param string|null $error Error message if operation failed
     */
    public function __construct(
        public bool $success,
        public string $postingId,
        public string $subledgerType,
        public int $entriesPosted = 0,
        public array $journalEntryIds = [],
        public ?string $error = null,
    ) {}
}
