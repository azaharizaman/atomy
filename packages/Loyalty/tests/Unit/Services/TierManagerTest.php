<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\Loyalty\Entities\LoyaltyProfile;
use Nexus\Loyalty\ValueObjects\PointBalance;
use Nexus\Loyalty\ValueObjects\TierStatus;
use Nexus\Loyalty\Services\TierManagementService;
use PHPUnit\Framework\TestCase;

final class TierManagerTest extends TestCase
{
    private TierManagementService $service;

    protected function setUp(): void
    {
        $this->service = new TierManagementService();
    }

    public function test_it_upgrades_user_tier_on_threshold(): void
    {
        $profile = $this->createProfile(6000); // Threshold for Gold is 5000

        $newStatus = $this->service->evaluateTierProgression($profile);

        $this->assertEquals('gold', $newStatus->tierId);
        $this->assertContains('free_shipping', $newStatus->benefits);
    }

    public function test_it_recalculates_status_on_history(): void
    {
        // Qualifying points in window is 16000 (Platinum is 15000)
        $profile = $this->createProfile(20000, ['qualifying_points_window' => 16000]);

        $newStatus = $this->service->recalculateTierStatus($profile, 365);

        $this->assertEquals('platinum', $newStatus->tierId);
    }

    public function test_tier_maintenance_logic(): void
    {
        // Member is currently Gold, but only has 1000 points (Bronze threshold)
        $now = new DateTimeImmutable();
        $currentTier = new TierStatus('gold', 'Gold Status', $now->modify('-1 month'), $now->modify('+11 months'));
        $profile = $this->createProfile(1000, [], $currentTier);

        $newStatus = $this->service->evaluateTierProgression($profile);

        // Should retain Gold due to maintenance period
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
