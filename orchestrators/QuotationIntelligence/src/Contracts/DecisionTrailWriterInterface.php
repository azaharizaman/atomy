<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Contracts;

/**
 * Writes immutable hash-chained decision trail entries.
 */
interface DecisionTrailWriterInterface
{
    /**
     * @param string $tenantId
     * @param string $rfqId
     * @param array<int, array{
     *   event_type: string,
     *   payload: array<string, mixed>
     * }> $entries
     * @param int $startingSequence Starting sequence number (must be >= 1)
     * @param string $previousHash Previous entry hash (64-char hex) or empty for first entry
     *
     * @return array<int, array{
     *   sequence: int,
     *   event_type: string,
     *   payload_hash: string,
     *   previous_hash: string,
     *   entry_hash: string,
     *   occurred_at: string
     * }>
     */
    public function write(
        string $tenantId,
        string $rfqId,
        array $entries,
        int $startingSequence = 1,
        string $previousHash = ''
    ): array;
}

