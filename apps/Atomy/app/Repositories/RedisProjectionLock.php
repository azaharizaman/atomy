<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Facades\Redis;
use Nexus\EventStream\Contracts\ProjectionLockInterface;
use Nexus\EventStream\Exceptions\LockAcquisitionException;
use Nexus\Tenant\Contracts\TenantContextInterface;

/**
 * Redis-based Projection Lock Implementation
 *
 * Provides distributed pessimistic locking for projection rebuilds using Redis.
 * Prevents concurrent projection execution across multiple workers/processes.
 *
 * Lock Key Format: "projection_lock:{tenant_id}:{projector_name}"
 * Lock Value: Unix timestamp of acquisition time (for age calculation)
 *
 * Requirements Coverage:
 * - FUN-EVS-7218: Projection rebuilds with pessimistic locks
 * - REL-EVS-7413: Prevent concurrent projection rebuilds
 * - BUS-EVS-7107: Tenant isolation
 *
 * @package App\Repositories
 */
final readonly class RedisProjectionLock implements ProjectionLockInterface
{
    /**
     * @param TenantContextInterface $tenantContext Tenant context for multi-tenancy
     */
    public function __construct(
        private TenantContextInterface $tenantContext
    ) {}

    /**
     * Acquire a lock for a projector with TTL (time-to-live)
     *
     * Uses Redis SET with NX (not exists) and EX (expiry) options for atomic lock acquisition.
     * Stores acquisition timestamp as lock value for age calculation.
     *
     * @param string $projectorName The name of the projector to lock
     * @param int $ttlSeconds Time-to-live in seconds (default: 3600 = 1 hour)
     * @return bool True if lock acquired, false if already locked
     * @throws LockAcquisitionException If Redis connection fails
     */
    public function acquire(string $projectorName, int $ttlSeconds = 3600): bool
    {
        try {
            $lockKey = $this->getLockKey($projectorName);
            $timestamp = time();

            // SET key value NX EX ttl
            // NX = Only set if key does not exist (atomic test-and-set)
            // EX = Set expiry time in seconds
            // Predis expects options as an array
            $result = Redis::set($lockKey, (string)$timestamp, 'EX', $ttlSeconds, 'NX');
            
            // Predis returns 'OK' on success, null on failure
            return $result !== null;
        } catch (\Exception $e) {
            throw new LockAcquisitionException(
                "Failed to acquire lock for projector '{$projectorName}': {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Release a lock for a projector
     *
     * Removes the lock key from Redis, allowing other processes to acquire it.
     *
     * @param string $projectorName The name of the projector to unlock
     * @return void
     */
    public function release(string $projectorName): void
    {
        $lockKey = $this->getLockKey($projectorName);
        Redis::del($lockKey);
    }

    /**
     * Check if a projector is currently locked
     *
     * @param string $projectorName The name of the projector to check
     * @return bool True if locked, false otherwise
     */
    public function isLocked(string $projectorName): bool
    {
        $lockKey = $this->getLockKey($projectorName);
        return Redis::exists($lockKey) === 1;
    }

    /**
     * Get the age of a lock in seconds (for zombie detection)
     *
     * Returns null if the lock does not exist.
     * Age is calculated from the acquisition timestamp stored in the lock value.
     *
     * Zombie Detection Pattern:
     * ```php
     * $age = $lock->getLockAge('MyProjector');
     * if ($age !== null && $age > 7200) { // 2 hours
     *     $lock->forceRelease('MyProjector'); // Release stale lock
     * }
     * ```
     *
     * @param string $projectorName The name of the projector
     * @return int|null Age in seconds, or null if not locked
     */
    public function getLockAge(string $projectorName): ?int
    {
        $lockKey = $this->getLockKey($projectorName);
        $timestamp = Redis::get($lockKey);

        if ($timestamp === null) {
            return null;
        }

        return time() - (int)$timestamp;
    }

    /**
     * Force release a lock (use with caution)
     *
     * This method should only be used when a lock is known to be stale/zombie
     * (e.g., process crashed, lock age exceeds reasonable threshold).
     *
     * WARNING: Force releasing an active lock can lead to concurrent projection
     * execution and data corruption. Always check lock age before force release.
     *
     * @param string $projectorName The name of the projector to force unlock
     * @return void
     */
    public function forceRelease(string $projectorName): void
    {
        // Force release is identical to normal release - just remove the key
        $this->release($projectorName);
    }

    /**
     * Generate tenant-scoped lock key
     *
     * Key Format: "projection_lock:{tenant_id}:{projector_name}"
     *
     * Examples:
     * - projection_lock:tenant-alpha:AccountBalanceProjector
     * - projection_lock:tenant-beta:InventoryProjector
     *
     * @param string $projectorName The projector name
     * @return string Fully qualified lock key
     */
    private function getLockKey(string $projectorName): string
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        return "projection_lock:{$tenantId}:{$projectorName}";
    }
}
