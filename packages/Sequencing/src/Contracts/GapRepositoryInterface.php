<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Contracts;

/**
 * Repository interface for managing gaps in sequences.
 *
 * Gaps occur when numbers are voided, cancelled, or transactions fail.
 */
interface GapRepositoryInterface
{
    /**
     * Record a gap in the sequence.
     *
     * @param string $number The missing/voided number
     * @param string|null $reason Optional reason for the gap
     */
    public function recordGap(SequenceInterface $sequence, string $number, ?string $reason = null): void;

    /**
     * Get all gaps for a sequence.
     *
     * @return array<string, mixed>[]
     */
    public function getGaps(SequenceInterface $sequence): array;

    /**
     * Get the next available gap to fill.
     *
     * @return string|null The number to reuse, or null if no gaps available
     */
    public function getNextGap(SequenceInterface $sequence): ?string;

    /**
     * Mark a gap as filled (reclaimed).
     */
    public function markGapFilled(SequenceInterface $sequence, string $number): void;

    /**
     * Get a report of all unfilled gaps.
     *
     * @return string[]
     */
    public function getGapReport(SequenceInterface $sequence): array;

    /**
     * Clear all gaps for a sequence.
     */
    public function clearGaps(SequenceInterface $sequence): void;
}
