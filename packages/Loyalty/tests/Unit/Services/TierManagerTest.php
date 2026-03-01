<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\Loyalty\Entities\LoyaltyProfile;
use Nexus\Loyalty\ValueObjects\PointBalance;
use Nexus\Loyalty\ValueObjects\TierStatus;
use Nexus\Loyalty\Services\TierManagementService;
use Nexus\Loyalty\Contracts\LoyaltySettingsInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class TierManagerTest extends TestCase
{
    private TierManagementService $service;
    private LoyaltySettingsInterface|MockObject $settings;

    protected function setUp(): void
    {
        $this->settings = $this->createMock(LoyaltySettingsInterface::class);
        $this->settings->method('getTierConfig')->willReturn([
            'bronze' => ['threshold' => 0, 'name' => 'Bronze Status', 'benefits' => ['standard_support']],
            'gold' => ['threshold' => 5000, 'name' => 'Gold Status', 'benefits' => ['free_shipping']],
            'platinum' => ['threshold' => 15000, 'name' => 'Platinum Status', 'benefits' => ['priority_support']],
        ]);
        $this->settings->method('getDefaultRetentionMonths')->willReturn(12);
        
        $this->service = new TierManagementService($this->settings);
    }

    public function test_it_evaluates_correct_tier_based_on_points(): void
    {
        // 1. Bronze (0 points)
        $profile = $this->createProfile(0);
        $status = $this->service->evaluateTierProgression($profile);
        $this->assertEquals('bronze', $status->tierId);

        // 2. Gold (5000 points)
        $profile = $this->createProfile(5500);
        $status = $this->service->evaluateTierProgression($profile);
        $this->assertEquals('gold', $status->tierId);

        // 3. Platinum (15000 points)
        $profile = $this->createProfile(20000);
        $status = $this->service->evaluateTierProgression($profile);
        $this->assertEquals('platinum', $status->tierId);
    }

    public function test_tier_retention_logic(): void
    {
        $now = new DateTimeImmutable();
        
        // Already Gold, earned 0 points in window, but status is still valid
        $currentTier = new TierStatus('gold', 'Gold Status', $now->modify('-1 month'), $now->modify('+11 months'));
        $profile = $this->createProfile(0, [], $currentTier);
        
        $newStatus = $this->service->evaluateTierProgression($profile);
        
        // Should retain Gold even if current points only qualify for Bronze
        $this->assertEquals('gold', $newStatus->tierId);
    }

    public function test_recalculate_tier_status(): void
    {
        // Simulate window points in metadata
        $profile = $this->createProfile(20000, ['qualifying_points_window' => 6000]);
        
        $newStatus = $this->service->recalculateTierStatus($profile, 365);
        
        // Should be Gold based on window points (6000), not Platinum from lifetime (20000)
        $this->assertEquals('gold', $newStatus->tierId);
    }

    private function createProfile(int $lifetimePoints, array $metadata = [], ?TierStatus $tier = null): LoyaltyProfile
    {
        $balance = new PointBalance($lifetimePoints, $lifetimePoints, []);
        $tier = $tier ?? new TierStatus('bronze', 'Bronze Status', new DateTimeImmutable());

        return new LoyaltyProfile(
            'member-123',
            'tenant-456',
            $balance,
            $tier,
            $metadata
        );
    }
}
