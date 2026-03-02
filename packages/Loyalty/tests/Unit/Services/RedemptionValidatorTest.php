<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\Loyalty\Entities\LoyaltyProfile;
use Nexus\Loyalty\ValueObjects\PointBalance;
use Nexus\Loyalty\ValueObjects\PointBucket;
use Nexus\Loyalty\ValueObjects\TierStatus;
use Nexus\Loyalty\Services\RedemptionValidator;
use Nexus\Loyalty\Contracts\LoyaltySettingsInterface;
use Nexus\Loyalty\Contracts\IdempotencyStoreInterface;
use Nexus\Loyalty\Exceptions\InsufficientPointsException;
use Nexus\Loyalty\Exceptions\InvalidRedemptionRequestException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;

final class RedemptionValidatorTest extends TestCase
{
    private RedemptionValidator $validator;
    private LoyaltySettingsInterface|MockObject $settings;
    private IdempotencyStoreInterface|MockObject $idempotencyStore;

    protected function setUp(): void
    {
        $this->settings = $this->createMock(LoyaltySettingsInterface::class);
        $this->settings->method('getMinBalanceThreshold')->willReturn(1000);
        $this->settings->method('getIncrementalStep')->willReturn(100);

        $this->idempotencyStore = $this->createMock(IdempotencyStoreInterface::class);

        $this->validator = new RedemptionValidator($this->settings, $this->idempotencyStore);
    }

    public function test_it_validates_basic_redemption(): void
    {
        $profile = $this->createProfile(2000);
        $this->assertTrue($this->validator->validateRedemption($profile, 500));
    }

    public function test_it_handles_idempotency(): void
    {
        $profile = $this->createProfile(2000);
        $token = 'existing-token';

        $this->idempotencyStore->expects($this->once())
            ->method('has')
            ->with($token)
            ->willReturn(true);

        $this->assertTrue($this->validator->validateRedemption($profile, 500, $token));
    }

    public function test_it_marks_token_as_processed(): void
    {
        $profile = $this->createProfile(2000);
        $token = 'new-token';

        $this->idempotencyStore->method('has')->willReturn(false);
        $this->idempotencyStore->expects($this->once())
            ->method('mark')
            ->with($token);

        $this->assertTrue($this->validator->validateRedemption($profile, 500, $token));
    }

    public function test_it_fails_on_inconsistent_balance(): void
    {
        $now = new DateTimeImmutable();
        $bucket = new PointBucket('b1', 1000, 1000, $now);
        // Inconsistent: totalAvailable (2000) != sum of buckets (1000)
        $balance = new PointBalance(2000, 2000, [$bucket]);
        $profile = new LoyaltyProfile('m1', 't1', $balance, new TierStatus('b', 'B', $now));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Data Integrity Violation');
        $this->validator->validateRedemption($profile, 500);
    }

    public function test_it_enforces_minimum_balance(): void
    {
        // Available points: 900, but minBalanceThreshold is 1000
        $profile = $this->createProfile(900);

        $this->expectException(InvalidRedemptionRequestException::class);
        $this->expectExceptionMessage('Minimum balance of 1000 required');
        $this->validator->validateRedemption($profile, 100);
    }

    public function test_it_enforces_incremental_multiples(): void
    {
        $profile = $this->createProfile(2000);

        // Redemption amount: 150, but incrementalStep is 100
        $this->expectException(InvalidRedemptionRequestException::class);
        $this->expectExceptionMessage('multiples of 100');
        $this->validator->validateRedemption($profile, 150);
    }

    public function test_it_checks_available_balance(): void
    {
        $profile = $this->createProfile(1200);

        $this->expectException(InsufficientPointsException::class);
        $this->validator->validateRedemption($profile, 1500);
    }

    public function test_fifo_expiry_check(): void
    {
        $now = new DateTimeImmutable();
        
        $expiredBucket = new PointBucket('b1', 1000, 1000, $now->modify('-2 years'), $now->modify('-1 year'));
        $validBucket = new PointBucket('b2', 500, 500, $now->modify('-1 month'));

        // PointBalance::fromBuckets already filters expired buckets for totalAvailable
        $balance = PointBalance::fromBuckets([$expiredBucket, $validBucket], 1500, $now);
        
        $profile = new LoyaltyProfile(
            'm1', 't1', $balance, 
            new TierStatus('bronze', 'Bronze', $now)
        );

        // 1000 in b1 is expired, only 500 in b2 is available.
        $this->expectException(InsufficientPointsException::class);
        $this->validator->validateRedemption($profile, 1000);
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
