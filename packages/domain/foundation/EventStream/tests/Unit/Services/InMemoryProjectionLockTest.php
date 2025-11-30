<?php

declare(strict_types=1);

namespace Nexus\EventStream\Tests\Unit\Services;

use Nexus\EventStream\Services\InMemoryProjectionLock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(InMemoryProjectionLock::class)]
final class InMemoryProjectionLockTest extends TestCase
{
    private InMemoryProjectionLock $lock;

    protected function setUp(): void
    {
        $this->lock = new InMemoryProjectionLock();
    }

    #[Test]
    public function it_implements_projection_lock_interface(): void
    {
        $this->assertInstanceOf(
            \Nexus\EventStream\Contracts\ProjectionLockInterface::class,
            $this->lock
        );
    }

    #[Test]
    public function it_acquires_lock_successfully(): void
    {
        $acquired = $this->lock->acquire('test-projector');

        $this->assertTrue($acquired);
        $this->assertTrue($this->lock->isLocked('test-projector'));
    }

    #[Test]
    public function it_rejects_second_acquire_attempt(): void
    {
        $this->lock->acquire('test-projector');
        $secondAcquire = $this->lock->acquire('test-projector');

        $this->assertFalse($secondAcquire);
    }

    #[Test]
    public function it_releases_lock(): void
    {
        $this->lock->acquire('test-projector');
        $this->lock->release('test-projector');

        $this->assertFalse($this->lock->isLocked('test-projector'));
    }

    #[Test]
    public function it_allows_reacquire_after_release(): void
    {
        $this->lock->acquire('test-projector');
        $this->lock->release('test-projector');
        $reacquired = $this->lock->acquire('test-projector');

        $this->assertTrue($reacquired);
    }

    #[Test]
    public function it_returns_lock_age(): void
    {
        $this->lock->acquire('test-projector');
        sleep(1);

        $age = $this->lock->getLockAge('test-projector');

        $this->assertIsInt($age);
        $this->assertGreaterThanOrEqual(1, $age);
    }

    #[Test]
    public function it_returns_null_age_for_unlocked(): void
    {
        $age = $this->lock->getLockAge('nonexistent');

        $this->assertNull($age);
    }

    #[Test]
    public function it_expires_lock_after_ttl(): void
    {
        $this->lock->acquire('test-projector', 1); // 1 second TTL
        sleep(2);

        $this->assertFalse($this->lock->isLocked('test-projector'));
    }

    #[Test]
    public function it_allows_reacquire_after_ttl_expiration(): void
    {
        $this->lock->acquire('test-projector', 1);
        sleep(2);
        $reacquired = $this->lock->acquire('test-projector');

        $this->assertTrue($reacquired);
    }

    #[Test]
    public function it_force_releases_lock(): void
    {
        $this->lock->acquire('test-projector');
        $this->lock->forceRelease('test-projector');

        $this->assertFalse($this->lock->isLocked('test-projector'));
    }

    #[Test]
    public function it_handles_multiple_projectors_independently(): void
    {
        $this->lock->acquire('projector-a');
        $this->lock->acquire('projector-b');

        $this->assertTrue($this->lock->isLocked('projector-a'));
        $this->assertTrue($this->lock->isLocked('projector-b'));

        $this->lock->release('projector-a');

        $this->assertFalse($this->lock->isLocked('projector-a'));
        $this->assertTrue($this->lock->isLocked('projector-b'));
    }

    #[Test]
    public function it_clears_all_locks(): void
    {
        $this->lock->acquire('projector-a');
        $this->lock->acquire('projector-b');
        $this->lock->clearAll();

        $this->assertFalse($this->lock->isLocked('projector-a'));
        $this->assertFalse($this->lock->isLocked('projector-b'));
    }
}
