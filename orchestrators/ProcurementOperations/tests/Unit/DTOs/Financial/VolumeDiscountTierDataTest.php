<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\DTOs\Financial;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Financial\VolumeDiscountTierData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(VolumeDiscountTierData::class)]
final class VolumeDiscountTierDataTest extends TestCase
{
    #[Test]
    public function it_creates_percentage_based_tier(): void
    {
        $effectiveFrom = new \DateTimeImmutable('2024-01-01');
        $effectiveTo = new \DateTimeImmutable('2024-12-31');
        
        $tier = VolumeDiscountTierData::percentageTier(
            tierId: 'tier-001',
            tenantId: 'tenant-001',
            vendorId: 'vendor-001',
            tierName: 'Gold Tier',
            minAmount: Money::of(10000.00, 'USD'),
            maxAmount: Money::of(50000.00, 'USD'),
            discountPercentage: 5.0,
            effectiveFrom: $effectiveFrom,
            effectiveTo: $effectiveTo,
        );

        $this->assertSame('tier-001', $tier->tierId);
        $this->assertSame('Gold Tier', $tier->tierName);
        $this->assertSame(10000.0, $tier->minAmount->getAmount());
        $this->assertSame(50000.0, $tier->maxAmount->getAmount());
        $this->assertSame(5.0, $tier->discountPercentage);
        $this->assertTrue($tier->isPercentageBased);
        $this->assertNull($tier->fixedDiscountAmount);
    }

    #[Test]
    public function it_creates_fixed_amount_tier(): void
    {
        $effectiveFrom = new \DateTimeImmutable('2024-01-01');
        
        $tier = VolumeDiscountTierData::fixedAmountTier(
            tierId: 'tier-002',
            tenantId: 'tenant-001',
            vendorId: 'vendor-001',
            tierName: 'Platinum Rebate',
            minAmount: Money::of(100000.00, 'USD'),
            maxAmount: null, // No upper limit
            fixedDiscountAmount: Money::of(5000.00, 'USD'),
            effectiveFrom: $effectiveFrom,
        );

        $this->assertSame('Platinum Rebate', $tier->tierName);
        $this->assertSame(100000.0, $tier->minAmount->getAmount());
        $this->assertNull($tier->maxAmount);
        $this->assertFalse($tier->isPercentageBased);
        $this->assertSame(5000.0, $tier->fixedDiscountAmount->getAmount());
    }

    #[Test]
    public function it_checks_if_amount_applies_to_tier(): void
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

        // Below minimum
        $this->assertFalse($tier->appliesTo(Money::of(5000.00, 'USD')));
        
        // At minimum (inclusive)
        $this->assertTrue($tier->appliesTo(Money::of(10000.00, 'USD')));
        
        // Within range
        $this->assertTrue($tier->appliesTo(Money::of(25000.00, 'USD')));
        
        // At maximum (exclusive)
        $this->assertFalse($tier->appliesTo(Money::of(50000.00, 'USD')));
    }

    #[Test]
    public function it_handles_tier_with_no_upper_limit(): void
    {
        $tier = VolumeDiscountTierData::percentageTier(
            tierId: 'tier-001',
            tenantId: 'tenant-001',
            vendorId: 'vendor-001',
            tierName: 'Enterprise Tier',
            minAmount: Money::of(100000.00, 'USD'),
            maxAmount: null, // No upper limit
            discountPercentage: 10.0,
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $this->assertTrue($tier->appliesTo(Money::of(100000.00, 'USD')));
        $this->assertTrue($tier->appliesTo(Money::of(500000.00, 'USD')));
        $this->assertTrue($tier->appliesTo(Money::of(1000000.00, 'USD')));
    }

    #[Test]
    public function it_checks_effectiveness_date(): void
    {
        $tier = VolumeDiscountTierData::percentageTier(
            tierId: 'tier-001',
            tenantId: 'tenant-001',
            vendorId: 'vendor-001',
            tierName: 'Seasonal Discount',
            minAmount: Money::of(10000.00, 'USD'),
            maxAmount: Money::of(50000.00, 'USD'),
            discountPercentage: 5.0,
            effectiveFrom: new \DateTimeImmutable('2024-06-01'),
            effectiveTo: new \DateTimeImmutable('2024-08-31'),
        );

        // Before effective period
        $this->assertFalse($tier->isEffective(new \DateTimeImmutable('2024-05-15')));
        
        // During effective period
        $this->assertTrue($tier->isEffective(new \DateTimeImmutable('2024-07-15')));
        
        // On effective start date
        $this->assertTrue($tier->isEffective(new \DateTimeImmutable('2024-06-01')));
        
        // On effective end date
        $this->assertTrue($tier->isEffective(new \DateTimeImmutable('2024-08-31')));
        
        // After effective period
        $this->assertFalse($tier->isEffective(new \DateTimeImmutable('2024-09-01')));
    }

    #[Test]
    public function it_calculates_percentage_discount(): void
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

        $discount = $tier->calculateDiscount(Money::of(20000.00, 'USD'));

        $this->assertSame(1000.0, $discount->getAmount()); // 5% of 20000
    }

    #[Test]
    public function it_calculates_fixed_discount(): void
    {
        $tier = VolumeDiscountTierData::fixedAmountTier(
            tierId: 'tier-002',
            tenantId: 'tenant-001',
            vendorId: 'vendor-001',
            tierName: 'Platinum Rebate',
            minAmount: Money::of(100000.00, 'USD'),
            maxAmount: null,
            fixedDiscountAmount: Money::of(5000.00, 'USD'),
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $discount = $tier->calculateDiscount(Money::of(150000.00, 'USD'));

        $this->assertSame(5000.0, $discount->getAmount()); // Fixed amount
    }

    #[Test]
    public function it_returns_zero_discount_if_amount_not_in_tier(): void
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

        $discount = $tier->calculateDiscount(Money::of(5000.00, 'USD'));

        $this->assertSame(0.0, $discount->getAmount());
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
            productCategoryId: 'cat-001',
        );

        $array = $tier->toArray();

        $this->assertSame('tier-001', $array['tier_id']);
        $this->assertSame('Gold Tier', $array['tier_name']);
        $this->assertSame(5.0, $array['discount_percentage']);
        $this->assertTrue($array['is_percentage_based']);
        $this->assertSame('cat-001', $array['product_category_id']);
        $this->assertArrayHasKey('min_amount', $array);
        $this->assertArrayHasKey('max_amount', $array);
    }

    #[Test]
    #[DataProvider('discountCalculationProvider')]
    public function it_calculates_discounts_correctly(
        float $purchaseAmount,
        float $discountPercentage,
        float $expectedDiscount,
    ): void {
        $tier = VolumeDiscountTierData::percentageTier(
            tierId: 'tier-001',
            tenantId: 'tenant-001',
            vendorId: 'vendor-001',
            tierName: 'Test Tier',
            minAmount: Money::of(0.00, 'USD'), // No minimum for testing
            maxAmount: null,
            discountPercentage: $discountPercentage,
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $discount = $tier->calculateDiscount(Money::of($purchaseAmount, 'USD'));

        $this->assertSame($expectedDiscount, $discount->getAmount());
    }

    /**
     * @return array<string, array{float, float, float}>
     */
    public static function discountCalculationProvider(): array
    {
        return [
            '5% on $10,000' => [10000.00, 5.0, 500.0],
            '10% on $25,000' => [25000.00, 10.0, 2500.0],
            '2.5% on $50,000' => [50000.00, 2.5, 1250.0],
            '0.5% on $100,000' => [100000.00, 0.5, 500.0],
        ];
    }
}
