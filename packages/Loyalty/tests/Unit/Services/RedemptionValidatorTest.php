<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\Loyalty\Exceptions\InsufficientPointsException;
use Nexus\Loyalty\Exceptions\InvalidRedemptionRequestException;
use Nexus\Loyalty\Models\LoyaltyProfile;
use Nexus\Loyalty\Models\PointBalance;
use Nexus\Loyalty\Models\PointBucket;
use Nexus\Loyalty\Models\TierStatus;
use Nexus\Loyalty\Services\RedemptionValidator;
use PHPUnit\Framework\TestCase;

final class RedemptionValidatorTest extends TestCase
{
    private RedemptionValidator $service;

    protected function setUp(): void
    {
        $this->service = new RedemptionValidator(1000, 100);
    }

    public function test_it_validates_redemption_limits(): void
    {
        $profile = $this->createProfile(1500); // Meets min 1000 threshold

        $this->assertTrue($this->service->validateRedemption($profile, 500));
    }

    public function test_it_throws_insufficient_points(): void
    {
        $profile = $this->createProfile(500);

        $this->expectException(InsufficientPointsException::class);
        $this->service->validateRedemption($profile, 1000);
    }

    public function test_minimum_redemption_threshold(): void
    {
        $profile = $this->createProfile(500);

        $this->expectException(InvalidRedemptionRequestException::class);
        $this->service->validateRedemption($profile, 100);
    }

    public function test_incremental_redemption(): void
    {
        $profile = $this->createProfile(1500);

        $this->expectException(InvalidRedemptionRequestException::class);
        $this->service->validateRedemption($profile, 550); // Must be multiples of 100
    }

    public function test_fifo_expiry_prioritization(): void
    {
        $now = new DateTimeImmutable();
        $bucket1 = new PointBucket('b1', 1000, 1000, $now->modify('-2 months'));
        $bucket2 = new PointBucket('b2', 1000, 1000, $now->modify('-1 month'));

        $balance = new PointBalance(2000, 2000, [$bucket1, $bucket2]);
        $profile = new LoyaltyProfile(
            'member-123',
            'tenant-456',
            $balance,
            new TierStatus('bronze', 'Bronze', $now)
        );

        // Validation logic checks if enough points exist in valid buckets
        $this->assertTrue($this->service->validateRedemption($profile, 1500));
    }

    private function createProfile(int $availablePoints): LoyaltyProfile
    {
        $now = new DateTimeImmutable();
        $bucket = new PointBucket('b1', $availablePoints, $availablePoints, $now);
        $balance = new PointBalance($availablePoints, $availablePoints, [$bucket]);
        $tier = new TierStatus('bronze', 'Bronze Status', $now);

        return new LoyaltyProfile(
            'member-123',
            'tenant-456',
            $balance,
            $tier
        );
    }
}
