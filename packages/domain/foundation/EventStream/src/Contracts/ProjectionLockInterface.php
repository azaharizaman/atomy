<?php

declare(strict_types=1);

namespace Nexus\EventStream\Contracts;

use Nexus\EventStream\Exceptions\LockAcquisitionException;

/**
 * Projection Lock Interface
 *
 * Manages pessimistic locks for projection rebuilds to prevent concurrent execution.
 * Supports dual drivers (Redis for distributed systems, Database for single-instance).
 *
 * CRITICAL: Prevents race conditions when multiple workers attempt to rebuild projections.
 *
 * Requirements satisfied:
 * - FUN-EVS-7218: Projection rebuilds with pessimistic locks
 * - REL-EVS-7413: Prevent concurrent projection rebuilds
 *
 * @package Nexus\EventStream\Contracts
 */
interface ProjectionLockInterface
{
    /**
     * Acquire a lock for a projector.
     *
     * @param string $projectorName Unique projector identifier
     * @param int $ttlSeconds Time-to-live in seconds (default: 3600 = 1 hour)
     * @return bool True if lock acquired, false if already locked
     * @throws LockAcquisitionException If lock driver connection fails
     */
    public function acquire(string $projectorName, int $ttlSeconds = 3600): bool;

    /**
     * Release a lock for a projector.
     *
     * @param string $projectorName Unique projector identifier
     * @return void
     */
    public function release(string $projectorName): void;

    /**
     * Check if a projector is currently locked.
     *
     * @param string $projectorName Unique projector identifier
     * @return bool True if locked, false otherwise
     */
    public function isLocked(string $projectorName): bool;

    /**
     * Get the age of a lock in seconds (for zombie detection).
     *
     * @param string $projectorName Unique projector identifier
     * @return int|null Lock age in seconds, or null if not locked
     */
    public function getLockAge(string $projectorName): ?int;

    /**
     * Force-release a lock (use with extreme caution).
     *
     * @param string $projectorName Unique projector identifier
     * @return void
     */
    public function forceRelease(string $projectorName): void;
}
