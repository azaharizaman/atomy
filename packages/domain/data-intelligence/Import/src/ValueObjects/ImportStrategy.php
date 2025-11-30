<?php

declare(strict_types=1);

namespace Nexus\Import\ValueObjects;

/**
 * Import execution strategy enumeration
 * 
 * Defines how the import process should handle batching and transactions.
 */
enum ImportStrategy: string
{
    case BATCH = 'batch';               // Process in batches with individual transactions
    case STREAM = 'stream';             // Stream rows individually (no batching)
    case TRANSACTIONAL = 'transactional'; // Single transaction for entire import

    /**
     * Check if strategy requires transaction management
     */
    public function requiresTransaction(): bool
    {
        return match($this) {
            self::TRANSACTIONAL, self::BATCH => true,
            self::STREAM => false,
        };
    }

    /**
     * Check if strategy supports streaming for large datasets
     */
    public function supportsStreaming(): bool
    {
        return match($this) {
            self::STREAM => true,
            self::BATCH, self::TRANSACTIONAL => false,
        };
    }

    /**
     * Get recommended batch size for this strategy
     */
    public function getRecommendedBatchSize(): ?int
    {
        return match($this) {
            self::BATCH => 500,
            self::TRANSACTIONAL => null,  // All rows in one batch
            self::STREAM => 1,
        };
    }

    /**
     * Get memory efficiency rating (1-5, higher is better)
     */
    public function getMemoryEfficiency(): int
    {
        return match($this) {
            self::STREAM => 5,      // Most memory efficient
            self::BATCH => 3,       // Moderate
            self::TRANSACTIONAL => 1, // Least efficient (all in memory)
        };
    }

    /**
     * Get human-readable strategy description
     */
    public function getDescription(): string
    {
        return match($this) {
            self::BATCH => 'Process in batches with individual transactions (500 rows/batch)',
            self::STREAM => 'Stream rows individually for maximum memory efficiency',
            self::TRANSACTIONAL => 'Single transaction for entire import (rollback on error)',
        };
    }
}
