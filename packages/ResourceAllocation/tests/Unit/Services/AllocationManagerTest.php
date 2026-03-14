<?php

declare(strict_types=1);

namespace Nexus\ResourceAllocation\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\ResourceAllocation\Contracts\AllocationPersistInterface;
use Nexus\ResourceAllocation\Contracts\AllocationQueryInterface;
use Nexus\ResourceAllocation\Exceptions\OverallocationException;
use Nexus\ResourceAllocation\Services\AllocationManager;
use Nexus\ResourceAllocation\ValueObjects\AllocationSummary;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class AllocationManagerTest extends TestCase
{
    private AllocationQueryInterface&MockObject $query;
    private AllocationPersistInterface&MockObject $persist;
    private AllocationManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->query = $this->createMock(AllocationQueryInterface::class);
        $this->persist = $this->createMock(AllocationPersistInterface::class);
        $this->manager = new AllocationManager($this->query, $this->persist);
    }

    public function test_allocate_persists_when_under_cap(): void
    {
        $alloc = new AllocationSummary('a1', 'u1', new DateTimeImmutable('2025-01-15'), 50.0);
        $this->query->method('getTotalPercentageByUserAndDate')->willReturn(0.0);
        $this->persist->expects(self::once())->method('persist')->with($alloc);
        $this->manager->allocate($alloc);
    }

    public function test_allocate_throws_when_exceeds_100_percent(): void
    {
        $alloc = new AllocationSummary('a1', 'u1', new DateTimeImmutable(), 60.0);
        $this->query->method('getTotalPercentageByUserAndDate')->willReturn(50.0);
        $this->persist->expects(self::never())->method('persist');
        $this->expectException(OverallocationException::class);
        $this->expectExceptionMessage('exceed');
        $this->manager->allocate($alloc);
    }
}
