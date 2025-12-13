<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\DTOs\Financial;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Financial\VolumeDiscountResult;
use Nexus\ProcurementOperations\DTOs\Financial\VolumeDiscountTierData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(VolumeDiscountResult::class)]
final class VolumeDiscountResultTest extends TestCase
{
    #[Test]
    public function it_creates_no_discount_result(): void
    {
        $result = VolumeDiscountResult::noDiscount(
            purchaseAmount: Money::of(5000.00, 'USD'),
            reason: 'Purchase amount below minimum tier threshold',
        );

        $this->assertFalse($result->hasDiscount);
        $this->assertSame(0.0, $result->discountAmount->getAmount());
        $this->assertSame(5000.0, $result->netAmount->getAmount());
        $this->assertNull($result->appliedTier);
        $this->assertSame('Purchase amount below minimum tier threshold', $result->reason);
    }

    #[Test]
    public function it_creates_single_tier_discount_result(): void
    {
        $tier = VolumeDiscountTierData::percentageTier(
            tierId: 'tier-001',
            tenantId: 'tenant-001',
            vendorId: 'vendor-001',
            tierName: 'Gold Tier',
            minAmount: Money::of(10000.00, 'USD'),
            maxAmount: Money::of(50000.00, 'USD'),
            discountPercentage: 5.0,
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $result = VolumeDiscountResult::withSingleTier(
            purchaseAmount: Money::of(20000.00, 'USD'),
            discountAmount: Money::of(1000.00, 'USD'),
            appliedTier: $tier,
        );

        $this->assertTrue($result->hasDiscount);
        $this->assertSame(1000.0, $result->discountAmount->getAmount());
        $this->assertSame(19000.0, $result->netAmount->getAmount());
        $this->assertSame($tier, $result->appliedTier);
        $this->assertSame(5.0, $result->getEffectiveDiscountPercentage());
    }

    #[Test]
    public function it_creates_multi_tier_discount_result(): void
    {
        $tier1 = VolumeDiscountTierData::percentageTier(
            tierId: 'tier-001',
            tenantId: 'tenant-001',
            vendorId: 'vendor-001',
            tierName: 'Gold Tier',
            minAmount: Money::of(10000.00, 'USD'),
            maxAmount: Money::of(50000.00, 'USD'),
            discountPercentage: 5.0,
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $tier2 = VolumeDiscountTierData::percentageTier(
            tierId: 'tier-002',
            tenantId: 'tenant-001',
            vendorId: 'vendor-001',
            tierName: 'Platinum Tier',
            minAmount: Money::of(50000.00, 'USD'),
            maxAmount: null,
            discountPercentage: 8.0,
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $result = VolumeDiscountResult::withMultipleTiers(
            purchaseAmount: Money::of(75000.00, 'USD'),
            discountAmount: Money::of(5000.00, 'USD'), // Combined discount
            appliedTiers: [$tier1, $tier2],
        );

        $this->assertTrue($result->hasDiscount);
        $this->assertSame(5000.0, $result->discountAmount->getAmount());
        $this->assertSame(70000.0, $result->netAmount->getAmount());
        $this->assertCount(2, $result->appliedTiers);
    }

    #[Test]
    public function it_calculates_effective_discount_percentage(): void
    {
        $tier = VolumeDiscountTierData::percentageTier(
            tierId: 'tier-001',
            tenantId: 'tenant-001',
            vendorId: 'vendor-001',
            tierName: 'Gold Tier',
            minAmount: Money::of(10000.00, 'USD'),
            maxAmount: Money::of(50000.00, 'USD'),
            discountPercentage: 5.0,
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $result = VolumeDiscountResult::withSingleTier(
            purchaseAmount: Money::of(20000.00, 'USD'),
            discountAmount: Money::of(1000.00, 'USD'),
            appliedTier: $tier,
        );

        // 1000 / 20000 = 5%
        $this->assertSame(5.0, $result->getEffectiveDiscountPercentage());
    }

    #[Test]
    public function it_generates_savings_summary(): void
    {
        $tier = VolumeDiscountTierData::percentageTier(
            tierId: 'tier-001',
            tenantId: 'tenant-001',
            vendorId: 'vendor-001',
            tierName: 'Gold Tier',
            minAmount: Money::of(10000.00, 'USD'),
            maxAmount: Money::of(50000.00, 'USD'),
            discountPercentage: 5.0,
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $result = VolumeDiscountResult::withSingleTier(
            purchaseAmount: Money::of(20000.00, 'USD'),
            discountAmount: Money::of(1000.00, 'USD'),
            appliedTier: $tier,
        );

        $summary = $result->getSavingsSummary();

        $this->assertStringContainsString('Gold Tier', $summary);
        $this->assertStringContainsString('5', $summary); // percentage
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $tier = VolumeDiscountTierData::percentageTier(
            tierId: 'tier-001',
            tenantId: 'tenant-001',
            vendorId: 'vendor-001',
            tierName: 'Gold Tier',
            minAmount: Money::of(10000.00, 'USD'),
            maxAmount: Money::of(50000.00, 'USD'),
            discountPercentage: 5.0,
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $result = VolumeDiscountResult::withSingleTier(
            purchaseAmount: Money::of(20000.00, 'USD'),
            discountAmount: Money::of(1000.00, 'USD'),
            appliedTier: $tier,
        );

        $array = $result->toArray();

        $this->assertTrue($array['has_discount']);
        $this->assertArrayHasKey('purchase_amount', $array);
        $this->assertArrayHasKey('discount_amount', $array);
        $this->assertArrayHasKey('net_amount', $array);
        $this->assertArrayHasKey('effective_discount_percentage', $array);
        $this->assertArrayHasKey('applied_tier', $array);
        $this->assertArrayHasKey('applied_tiers', $array);
    }

    #[Test]
    public function it_handles_zero_discount_percentage_when_no_discount(): void
    {
        $result = VolumeDiscountResult::noDiscount(
            purchaseAmount: Money::of(5000.00, 'USD'),
            reason: 'No tier applies',
        );

        $this->assertSame(0.0, $result->getEffectiveDiscountPercentage());
    }

    #[Test]
    public function it_generates_summary_for_no_discount(): void
    {
        $result = VolumeDiscountResult::noDiscount(
            purchaseAmount: Money::of(5000.00, 'USD'),
            reason: 'Purchase amount below minimum tier threshold',
        );

        $summary = $result->getSavingsSummary();

        $this->assertStringContainsString('No discount', $summary);
    }
}
