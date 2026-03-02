<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\Loyalty\ValueObjects\PointBucket;
use Nexus\Loyalty\Services\ExpiryService;
use Nexus\Loyalty\Contracts\LoyaltySettingsInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class ExpiryServiceTest extends TestCase
{
    private ExpiryService $service;
    private LoyaltySettingsInterface|MockObject $settings;

    protected function setUp(): void
    {
        $this->settings = $this->createMock(LoyaltySettingsInterface::class);
        $this->settings->method('getDefaultExpiryMonths')->willReturn(12);

        $this->service = new ExpiryService($this->settings);
    }

    public function test_expiry_date_calculation(): void
    {
        $accruedAt = new DateTimeImmutable('2024-01-01');
        $expected = new DateTimeImmutable('2025-01-01');

        $this->assertEquals($expected, $this->service->calculateExpiryDate($accruedAt));
    }

    public function test_get_expiring_soon(): void
    {
        $now = new DateTimeImmutable('2024-01-01');
        $soon = $now->modify('+15 days');
        $later = $now->modify('+45 days');

        $bucket1 = new PointBucket('b1', 100, 100, $now, $soon);
        $bucket2 = new PointBucket('b2', 100, 100, $now, $later);

        $buckets = [$bucket1, $bucket2];
        $expiring = $this->service->getExpiringSoon($buckets, 30, $now);

        $this->assertCount(1, $expiring);
        $this->assertEquals('b1', array_values($expiring)[0]->id);
    }

    public function test_calculate_expired_points(): void
    {
        $now = new DateTimeImmutable('2024-01-01');
        $past = $now->modify('-1 day');
        $future = $now->modify('+1 day');

        $bucket1 = new PointBucket('b1', 100, 100, $past, $past); // Expired
        $bucket2 = new PointBucket('b2', 200, 200, $past, $future); // Not expired

        $this->assertEquals(100, $this->service->calculateExpiredPoints([$bucket1, $bucket2], $now));
    }
}
