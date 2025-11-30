<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Contracts;

/**
 * Repository interface for counter state management.
 *
 * Counters track the current value and reset information for sequences.
 */
interface CounterRepositoryInterface
{
    /**
     * Get the current counter value with database lock (SELECT FOR UPDATE).
     *
     * This method MUST acquire a database-level lock to ensure atomicity.
     *
     * @return int Current counter value
     */
    public function getCurrentValueWithLock(SequenceInterface $sequence): int;

    /**
     * Get the current counter value without locking (for preview).
     *
     * @return int Current counter value
     */
    public function getCurrentValue(SequenceInterface $sequence): int;

    /**
     * Increment the counter by the step size.
     *
     * This method should be called within a transaction after acquiring the lock.
     */
    public function increment(SequenceInterface $sequence, int $stepSize): int;

    /**
     * Reset the counter to the initial value.
     */
    public function reset(SequenceInterface $sequence): void;

    /**
     * Manually override the counter value.
     *
     * @throws \Nexus\Sequencing\Exceptions\InvalidCounterValueException
     */
    public function setCounterValue(SequenceInterface $sequence, int $value): void;

    /**
     * Get the last reset timestamp for the counter.
     *
     * @return \DateTimeInterface|null
     */
    public function getLastResetAt(SequenceInterface $sequence): ?\DateTimeInterface;

    /**
     * Check if the counter needs to be reset based on reset period.
     */
    public function needsReset(SequenceInterface $sequence): bool;

    /**
     * Get the total generation count for the current reset period.
     */
    public function getGenerationCount(SequenceInterface $sequence): int;
}
