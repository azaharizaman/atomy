<?php

declare(strict_types=1);

namespace Nexus\ResourceAllocation\Tests\Unit\ValueObjects;

use DateTimeImmutable;
use Nexus\ResourceAllocation\ValueObjects\AllocationSummary;
use PHPUnit\Framework\TestCase;

final class AllocationSummaryTest extends TestCase
{
    public function test_percentage_out_of_range_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('0 and 100');
        new AllocationSummary('1', 'u1', new DateTimeImmutable(), 150.0);
    }

    public function test_accepts_valid_percentage(): void
    {
        $a = new AllocationSummary('1', 'u1', new DateTimeImmutable('2025-01-15'), 50.0);
        self::assertSame(50.0, $a->percentage);
    }
}
