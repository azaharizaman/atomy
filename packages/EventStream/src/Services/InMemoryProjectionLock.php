<?php

declare(strict_types=1);

namespace Nexus\EventStream\Services;

use Nexus\EventStream\Contracts\ProjectionLockInterface;

/**
 * In-Memory Projection Lock
 *
 * Simple in-memory lock implementation for testing and single-process scenarios.
 * NOT suitable for distributed systems (use Redis/Database lock in production).
 *
 * @package Nexus\EventStream\Services
 */
final class InMemoryProjectionLock implements ProjectionLockInterface
{
    /**
     * @var array<string, array{acquired_at: int, ttl: int}>
     */
    private array $locks = [];

    /**
     * {@inheritDoc}
     */
    public function acquire(string $projectorName, int $ttlSeconds = 3600): bool
    {
        // Check if already locked and not expired
        if ($this->isLocked($projectorName)) {
            return false;
        }

        // Acquire lock
        $this->locks[$projectorName] = [
            'acquired_at' => time(),
            'ttl' => $ttlSeconds,
        ];

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function release(string $projectorName): void
    {
        unset($this->locks[$projectorName]);
    }

    /**
     * {@inheritDoc}
     */
    public function isLocked(string $projectorName): bool
    {
        if (!isset($this->locks[$projectorName])) {
            return false;
        }

        $lock = $this->locks[$projectorName];
        $age = time() - $lock['acquired_at'];

        // Lock expired?
        if ($age > $lock['ttl']) {
            unset($this->locks[$projectorName]);
            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getLockAge(string $projectorName): ?int
    {
        if (!isset($this->locks[$projectorName])) {
            return null;
        }

        return time() - $this->locks[$projectorName]['acquired_at'];
    }

    /**
     * {@inheritDoc}
     */
    public function forceRelease(string $projectorName): void
    {
        $this->release($projectorName);
    }

    /**
     * Clear all locks (for testing).
     *
     * @return void
     */
    public function clearAll(): void
    {
        $this->locks = [];
    }
}
