<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Enums;

use Nexus\ProcurementOperations\Enums\VendorPortalTier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(VendorPortalTier::class)]
final class VendorPortalTierTest extends TestCase
{
    #[Test]
    public function all_tiers_have_valid_values(): void
    {
        $tiers = VendorPortalTier::cases();

        $this->assertNotEmpty($tiers);
        $this->assertCount(4, $tiers);

        foreach ($tiers as $tier) {
            $this->assertNotEmpty($tier->value);
        }
    }

    #[Test]
    #[DataProvider('rateLimitProvider')]
    public function getRateLimit_returns_expected_limit(
        VendorPortalTier $tier,
        int $expectedLimit,
    ): void {
        $this->assertEquals($expectedLimit, $tier->getRateLimit());
    }

    /**
     * @return iterable<array{VendorPortalTier, int}>
     */
    public static function rateLimitProvider(): iterable
    {
        yield 'STANDARD has 100 req/min' => [VendorPortalTier::STANDARD, 100];
        yield 'PREMIUM has 500 req/min' => [VendorPortalTier::PREMIUM, 500];
        yield 'ENTERPRISE has 1000 req/min' => [VendorPortalTier::ENTERPRISE, 1000];
        yield 'SUSPENDED has 0 req/min' => [VendorPortalTier::SUSPENDED, 0];
    }

    #[Test]
    public function getDescription_returns_readable_string(): void
    {
        foreach (VendorPortalTier::cases() as $tier) {
            $description = $tier->getDescription();

            $this->assertNotEmpty($description);
            $this->assertIsString($description);
        }
    }

    #[Test]
    public function isActive_returns_true_for_active_tiers(): void
    {
        $this->assertTrue(VendorPortalTier::STANDARD->isActive());
        $this->assertTrue(VendorPortalTier::PREMIUM->isActive());
        $this->assertTrue(VendorPortalTier::ENTERPRISE->isActive());
        $this->assertFalse(VendorPortalTier::SUSPENDED->isActive());
    }

    #[Test]
    public function getAllowedFeatures_returns_tier_features(): void
    {
        $standardFeatures = VendorPortalTier::STANDARD->getAllowedFeatures();
        $premiumFeatures = VendorPortalTier::PREMIUM->getAllowedFeatures();
        $enterpriseFeatures = VendorPortalTier::ENTERPRISE->getAllowedFeatures();
        $suspendedFeatures = VendorPortalTier::SUSPENDED->getAllowedFeatures();

        // Standard should have basic features
        $this->assertNotEmpty($standardFeatures);

        // Premium should have more features than standard
        $this->assertGreaterThanOrEqual(
            count($standardFeatures),
            count($premiumFeatures),
        );

        // Enterprise should have most features
        $this->assertGreaterThanOrEqual(
            count($premiumFeatures),
            count($enterpriseFeatures),
        );

        // Suspended should have minimal or no features
        $this->assertLessThanOrEqual(
            count($standardFeatures),
            count($suspendedFeatures),
        );
    }

    #[Test]
    public function hasFeature_checks_feature_availability(): void
    {
        // Basic features available to all active tiers
        $this->assertTrue(VendorPortalTier::STANDARD->hasFeature('view_orders'));
        $this->assertTrue(VendorPortalTier::PREMIUM->hasFeature('view_orders'));
        $this->assertTrue(VendorPortalTier::ENTERPRISE->hasFeature('view_orders'));

        // Suspended has no features
        $this->assertFalse(VendorPortalTier::SUSPENDED->hasFeature('view_orders'));
    }

    #[Test]
    public function canUpgradeTo_validates_upgrade_path(): void
    {
        // Standard can upgrade to Premium or Enterprise
        $this->assertTrue(VendorPortalTier::STANDARD->canUpgradeTo(VendorPortalTier::PREMIUM));
        $this->assertTrue(VendorPortalTier::STANDARD->canUpgradeTo(VendorPortalTier::ENTERPRISE));

        // Premium can upgrade to Enterprise
        $this->assertTrue(VendorPortalTier::PREMIUM->canUpgradeTo(VendorPortalTier::ENTERPRISE));

        // Cannot downgrade via upgrade
        $this->assertFalse(VendorPortalTier::ENTERPRISE->canUpgradeTo(VendorPortalTier::PREMIUM));
        $this->assertFalse(VendorPortalTier::PREMIUM->canUpgradeTo(VendorPortalTier::STANDARD));

        // Cannot upgrade to same tier
        $this->assertFalse(VendorPortalTier::STANDARD->canUpgradeTo(VendorPortalTier::STANDARD));

        // Suspended can upgrade to any active tier
        $this->assertTrue(VendorPortalTier::SUSPENDED->canUpgradeTo(VendorPortalTier::STANDARD));
    }

    #[Test]
    public function getBurstLimit_returns_higher_than_rate_limit(): void
    {
        foreach (VendorPortalTier::cases() as $tier) {
            if ($tier === VendorPortalTier::SUSPENDED) {
                continue; // Skip suspended
            }

            $burstLimit = $tier->getBurstLimit();
            $rateLimit = $tier->getRateLimit();

            $this->assertGreaterThanOrEqual(
                $rateLimit,
                $burstLimit,
                "Burst limit for {$tier->value} should be >= rate limit",
            );
        }
    }

    #[Test]
    public function getMonthlyApiQuota_returns_valid_quota(): void
    {
        foreach (VendorPortalTier::cases() as $tier) {
            $quota = $tier->getMonthlyApiQuota();

            if ($tier === VendorPortalTier::SUSPENDED) {
                $this->assertEquals(0, $quota);
            } else {
                $this->assertGreaterThan(0, $quota);
            }
        }
    }

    #[Test]
    public function enterprise_has_highest_limits(): void
    {
        $enterpriseRate = VendorPortalTier::ENTERPRISE->getRateLimit();
        $premiumRate = VendorPortalTier::PREMIUM->getRateLimit();
        $standardRate = VendorPortalTier::STANDARD->getRateLimit();

        $this->assertGreaterThan($premiumRate, $enterpriseRate);
        $this->assertGreaterThan($standardRate, $premiumRate);
    }
}
