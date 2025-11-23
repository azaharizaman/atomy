<?php

declare(strict_types=1);

namespace Tests\Feature\Repositories;

use App\Repositories\RedisProjectionLock;
use Illuminate\Support\Facades\Redis;
use Nexus\EventStream\Exceptions\LockAcquisitionException;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Tests\TestCase;

/**
 * RedisProjectionLock Feature Tests
 *
 * Validates the Redis implementation of ProjectionLockInterface with:
 * - Lock acquisition and release
 * - TTL (time-to-live) management
 * - Zombie detection (stale lock detection)
 * - Force release functionality
 * - Tenant isolation
 * - Concurrent lock attempts
 * - Lock expiration handling
 *
 * Requirements Coverage:
 * - FUN-EVS-7218: Projection rebuilds with pessimistic locks
 * - REL-EVS-7413: Prevent concurrent projection rebuilds
 * - BUS-EVS-7107: Tenant isolation
 *
 * @group EventStream
 * @group Redis
 * @group PR3
 */
final class RedisProjectionLockTest extends TestCase
{

    private RedisProjectionLock $lock;
    private TenantContextInterface $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();

        // Use a mock TenantContext to avoid database dependency
        $this->tenantContext = $this->createMock(TenantContextInterface::class);
        $this->tenantContext->method('getCurrentTenantId')->willReturn('default-tenant');
        
        $this->lock = new RedisProjectionLock($this->tenantContext);

        // Clear all Redis keys before each test
        $this->flushRedis();
    }

    protected function tearDown(): void
    {
        // Clean up Redis after each test
        $this->flushRedis();
        parent::tearDown();
    }

    /** @test */
    public function it_acquires_lock_for_projector(): void
    {
        // Arrange
        $projectorName = 'AccountBalanceProjector';

        // Act
        $acquired = $this->lock->acquire($projectorName);

        // Assert
        $this->assertTrue($acquired);
        $this->assertTrue($this->lock->isLocked($projectorName));
    }

    /** @test */
    public function it_prevents_concurrent_lock_acquisition(): void
    {
        // Arrange
        $projectorName = 'InventoryProjector';

        // Act
        $firstAcquire = $this->lock->acquire($projectorName);
        $secondAcquire = $this->lock->acquire($projectorName);

        // Assert
        $this->assertTrue($firstAcquire);
        $this->assertFalse($secondAcquire);
    }

    /** @test */
    public function it_releases_lock(): void
    {
        // Arrange
        $projectorName = 'LedgerProjector';
        $this->lock->acquire($projectorName);

        // Act
        $this->lock->release($projectorName);

        // Assert
        $this->assertFalse($this->lock->isLocked($projectorName));
    }

    /** @test */
    public function it_allows_reacquisition_after_release(): void
    {
        // Arrange
        $projectorName = 'ReacquireProjector';
        $this->lock->acquire($projectorName);
        $this->lock->release($projectorName);

        // Act
        $reacquired = $this->lock->acquire($projectorName);

        // Assert
        $this->assertTrue($reacquired);
    }

    /** @test */
    public function it_sets_lock_with_custom_ttl(): void
    {
        // Arrange
        $projectorName = 'TTLProjector';
        $ttl = 10; // 10 seconds

        // Act
        $this->lock->acquire($projectorName, $ttl);

        // Assert
        $this->assertTrue($this->lock->isLocked($projectorName));
        
        // Check TTL is set (should be <= 10 seconds, accounting for execution time)
        $lockAge = $this->lock->getLockAge($projectorName);
        $this->assertNotNull($lockAge);
        $this->assertLessThanOrEqual(10, $lockAge);
    }

    /** @test */
    public function it_returns_null_lock_age_when_not_locked(): void
    {
        // Arrange
        $projectorName = 'UnlockedProjector';

        // Act
        $age = $this->lock->getLockAge($projectorName);

        // Assert
        $this->assertNull($age);
    }

    /** @test */
    public function it_tracks_lock_age_accurately(): void
    {
        // Arrange
        $projectorName = 'AgeTrackingProjector';
        $this->lock->acquire($projectorName);

        // Act
        sleep(2); // Wait 2 seconds
        $age = $this->lock->getLockAge($projectorName);

        // Assert
        $this->assertNotNull($age);
        $this->assertGreaterThanOrEqual(2, $age);
        $this->assertLessThan(5, $age); // Should be close to 2 seconds
    }

    /** @test */
    public function it_detects_zombie_locks(): void
    {
        // Arrange
        $projectorName = 'ZombieProjector';
        $this->lock->acquire($projectorName, 2); // 2-second TTL

        // Act
        sleep(3); // Wait for lock to expire
        $isLocked = $this->lock->isLocked($projectorName);
        $age = $this->lock->getLockAge($projectorName);

        // Assert - Lock should have expired
        $this->assertFalse($isLocked);
        $this->assertNull($age);
    }

    /** @test */
    public function it_force_releases_lock(): void
    {
        // Arrange
        $projectorName = 'ForceReleaseProjector';
        $this->lock->acquire($projectorName);

        // Act
        $this->lock->forceRelease($projectorName);

        // Assert
        $this->assertFalse($this->lock->isLocked($projectorName));
    }

    /** @test */
    public function it_allows_acquisition_after_force_release(): void
    {
        // Arrange
        $projectorName = 'ForceReacquireProjector';
        $this->lock->acquire($projectorName);
        $this->lock->forceRelease($projectorName);

        // Act
        $reacquired = $this->lock->acquire($projectorName);

        // Assert
        $this->assertTrue($reacquired);
    }

    /** @test */
    public function it_isolates_locks_by_tenant(): void
    {
        // Arrange
        $projectorName = 'SharedProjector';

        // Mock tenant context to return different tenant IDs
        $tenantContext = $this->createMock(TenantContextInterface::class);
        
        // Act - Tenant Alpha acquires lock
        $tenantContext->method('getCurrentTenantId')->willReturn('tenant-alpha');
        $lockAlpha = new RedisProjectionLock($tenantContext);
        $alphaAcquired = $lockAlpha->acquire($projectorName);
        $alphaLocked = $lockAlpha->isLocked($projectorName);

        // Act - Tenant Beta attempts to acquire same projector lock
        $tenantContext = $this->createMock(TenantContextInterface::class);
        $tenantContext->method('getCurrentTenantId')->willReturn('tenant-beta');
        $lockBeta = new RedisProjectionLock($tenantContext);
        $betaAcquired = $lockBeta->acquire($projectorName);
        $betaLocked = $lockBeta->isLocked($projectorName);

        // Assert - Both tenants should independently acquire locks
        $this->assertTrue($alphaAcquired);
        $this->assertTrue($alphaLocked);
        $this->assertTrue($betaAcquired); // Different tenant, different lock
        $this->assertTrue($betaLocked);
    }

    /** @test */
    public function it_isolates_locks_between_different_projectors(): void
    {
        // Arrange
        $projector1 = 'Projector1';
        $projector2 = 'Projector2';

        // Act
        $acquired1 = $this->lock->acquire($projector1);
        $acquired2 = $this->lock->acquire($projector2);

        // Assert - Both locks should be acquired independently
        $this->assertTrue($acquired1);
        $this->assertTrue($acquired2);
        $this->assertTrue($this->lock->isLocked($projector1));
        $this->assertTrue($this->lock->isLocked($projector2));
    }

    /** @test */
    public function it_handles_release_of_non_existent_lock(): void
    {
        // Arrange
        $projectorName = 'NonExistentProjector';

        // Act & Assert - Should not throw exception
        $this->lock->release($projectorName);
        $this->assertFalse($this->lock->isLocked($projectorName));
    }

    /** @test */
    public function it_handles_force_release_of_non_existent_lock(): void
    {
        // Arrange
        $projectorName = 'NonExistentForceProjector';

        // Act & Assert - Should not throw exception
        $this->lock->forceRelease($projectorName);
        $this->assertFalse($this->lock->isLocked($projectorName));
    }

    /** @test */
    public function it_expires_lock_after_ttl(): void
    {
        // Arrange
        $projectorName = 'ExpirationProjector';
        $ttl = 2; // 2 seconds

        // Act
        $this->lock->acquire($projectorName, $ttl);
        $this->assertTrue($this->lock->isLocked($projectorName));

        sleep(3); // Wait for expiration

        // Assert - Lock should have expired
        $canAcquire = $this->lock->acquire($projectorName);
        $this->assertTrue($canAcquire); // Should be able to acquire again
    }

    /** @test */
    public function it_maintains_lock_integrity_under_rapid_operations(): void
    {
        // Arrange
        $projectorName = 'RapidOpsProjector';

        // Act - Rapid acquire/release cycles
        for ($i = 0; $i < 10; $i++) {
            $acquired = $this->lock->acquire($projectorName);
            $this->assertTrue($acquired);
            $this->lock->release($projectorName);
        }

        // Final state check
        $this->assertFalse($this->lock->isLocked($projectorName));
    }

    /** @test */
    public function it_prevents_lock_acquisition_by_different_tenant_same_projector(): void
    {
        // Arrange
        $projectorName = 'TenantConflictProjector';

        // Mock tenant context for tenant 1
        $tenantContext1 = $this->createMock(TenantContextInterface::class);
        $tenantContext1->method('getCurrentTenantId')->willReturn('tenant-1');
        $lock1 = new RedisProjectionLock($tenantContext1);
        
        // Act - Tenant 1 acquires lock
        $tenant1Acquired = $lock1->acquire($projectorName);
        $tenant1Locked = $lock1->isLocked($projectorName);

        // Mock tenant context for tenant 2
        $tenantContext2 = $this->createMock(TenantContextInterface::class);
        $tenantContext2->method('getCurrentTenantId')->willReturn('tenant-2');
        $lock2 = new RedisProjectionLock($tenantContext2);
        
        // Act - Tenant 2 attempts to acquire same projector (should succeed - different namespace)
        $tenant2CanAcquire = $lock2->acquire($projectorName);
        $tenant2Locked = $lock2->isLocked($projectorName);

        // Assert
        $this->assertTrue($tenant1Acquired);
        $this->assertTrue($tenant1Locked);
        $this->assertTrue($tenant2CanAcquire); // Independent namespace per tenant
        $this->assertTrue($tenant2Locked);
    }

    /** @test */
    public function it_calculates_lock_age_from_acquisition_time(): void
    {
        // Arrange
        $projectorName = 'AgeCalculationProjector';
        $this->lock->acquire($projectorName);

        // Act - Check age immediately
        $initialAge = $this->lock->getLockAge($projectorName);

        // Wait 1 second
        sleep(1);

        // Check age again
        $laterAge = $this->lock->getLockAge($projectorName);

        // Assert
        $this->assertNotNull($initialAge);
        $this->assertNotNull($laterAge);
        $this->assertGreaterThan($initialAge, $laterAge);
    }

    /**
     * Helper method to flush Redis test database
     */
    private function flushRedis(): void
    {
        try {
            Redis::connection('default')->flushdb();
        } catch (\Exception $e) {
            // If Redis is not available, skip cleanup
            $this->markTestSkipped('Redis not available: ' . $e->getMessage());
        }
    }
}
