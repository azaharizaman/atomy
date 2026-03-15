<?php

declare(strict_types=1);

namespace Nexus\ResourceAllocation\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\ResourceAllocation\Contracts\AllocationQueryInterface;
use Nexus\ResourceAllocation\Services\OverallocationCheckerService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class OverallocationCheckerServiceTest extends TestCase
{
    public function test_not_overallocated_when_at_100(): void
    {
        $query = $this->createMock(AllocationQueryInterface::class);
        $query->method('getTotalPercentageByUserAndDate')->willReturn(100.0);
        $checker = new OverallocationCheckerService($query);
        self::assertFalse($checker->isOverallocated('u1', new DateTimeImmutable()));
    }

    public function test_overallocated_when_over_100(): void
    {
        $query = $this->createMock(AllocationQueryInterface::class);
        $query->method('getTotalPercentageByUserAndDate')->willReturn(110.0);
        $checker = new OverallocationCheckerService($query);
        self::assertTrue($checker->isOverallocated('u1', new DateTimeImmutable()));
    }
}
