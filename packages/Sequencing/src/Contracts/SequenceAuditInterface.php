<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Contracts;

/**
 * Audit interface for tracking sequence-related events.
 *
 * This interface defines methods for logging critical sequence operations
 * for compliance, debugging, and analytics purposes.
 */
interface SequenceAuditInterface
{
    /**
     * Log a pattern creation event.
     *
     * @param array<string, mixed> $metadata
     */
    public function logPatternCreated(SequenceInterface $sequence, array $metadata = []): void;

    /**
     * Log a pattern modification event.
     *
     * @param array<string, mixed> $changes
     */
    public function logPatternModified(SequenceInterface $sequence, array $changes): void;

    /**
     * Log a counter reset event.
     *
     * @param int $oldValue
     * @param int $newValue
     * @param string $reason
     */
    public function logCounterReset(SequenceInterface $sequence, int $oldValue, int $newValue, string $reason): void;

    /**
     * Log a manual counter override event.
     *
     * @param int $oldValue
     * @param int $newValue
     * @param string|null $performedBy User identifier
     */
    public function logCounterOverridden(SequenceInterface $sequence, int $oldValue, int $newValue, ?string $performedBy = null): void;

    /**
     * Log when exhaustion threshold is reached.
     *
     * @param int $currentValue
     * @param int $threshold
     */
    public function logExhaustionThresholdReached(SequenceInterface $sequence, int $currentValue, int $threshold): void;

    /**
     * Log a pattern version creation event.
     *
     * @param string $oldPattern
     * @param string $newPattern
     * @param \DateTimeInterface $effectiveFrom
     */
    public function logPatternVersionCreated(SequenceInterface $sequence, string $oldPattern, string $newPattern, \DateTimeInterface $effectiveFrom): void;

    /**
     * Log a number generation event.
     *
     * @param string $generatedNumber
     * @param array<string, mixed> $context
     */
    public function logNumberGenerated(SequenceInterface $sequence, string $generatedNumber, array $context = []): void;

    /**
     * Log a gap reclaim event.
     *
     * @param string $number
     */
    public function logGapReclaimed(SequenceInterface $sequence, string $number): void;

    /**
     * Log a sequence lock/unlock event.
     *
     * @param bool $isLocked
     * @param string|null $performedBy
     */
    public function logLockStatusChanged(SequenceInterface $sequence, bool $isLocked, ?string $performedBy = null): void;
}
