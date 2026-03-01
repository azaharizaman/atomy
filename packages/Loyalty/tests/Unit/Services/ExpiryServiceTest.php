<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\Loyalty\ValueObjects\PointBucket;
use Nexus\Loyalty\Services\ExpiryService;
use PHPUnit\Framework\TestCase;

final class ExpiryServiceTest extends TestCase
{
    private ExpiryService $service;

    protected function setUp(): void
    {
        $this->service = new ExpiryService(12);
    }

    public function test_it_calculates_expiry_dates(): void
    {
        $accruedAt = new DateTimeImmutable('2026-01-01');
        $expected = new DateTimeImmutable('2027-01-01');

        $this->assertEquals($expected, $this->service->calculateExpiryDate($accruedAt));
    }

    public function test_it_identifies_expiring_soon(): void
    {
        $now = new DateTimeImmutable();
        $soon = $now->modify('+10 days');
        $later = $now->modify('+60 days');

        $bucket1 = new PointBucket('b1', 100, 100, $now, $soon);
        $bucket2 = new PointBucket('b2', 100, 100, $now, $later);

        $expiring = $this->service->getExpiringSoon([$bucket1, $bucket2], 30, $now);

        $this->assertCount(1, $expiring);
        $this->assertEquals('b1', reset($expiring)->id);
    }

    public function test_it_calculates_expired_points_for_writeoff(): void
    {
        $now = new DateTimeImmutable();
        $past = $now->modify('-1 day');
        $future = $now->modify('+1 day');

        $bucket1 = new PointBucket('b1', 100, 100, $past, $past); // Expired
        $bucket2 = new PointBucket('b2', 200, 200, $past, $future); // Not expired

        $expiredTotal = $this->service->calculateExpiredPoints([$bucket1, $bucket2], $now);

        $this->assertEquals(100, $expiredTotal);
    }
}
