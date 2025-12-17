<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Financial\VolumeDiscountResult;
use Nexus\ProcurementOperations\DTOs\Financial\VolumeDiscountTierData;
use Nexus\ProcurementOperations\Services\VolumeDiscountService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(VolumeDiscountService::class)]
final class VolumeDiscountServiceTest extends TestCase
{
    private VolumeDiscountService $service;

    protected function setUp(): void
    {
        $this->service = new VolumeDiscountService(
            logger: new NullLogger(),
        );
    }

    #[Test]
    public function it_creates_standard_discount_tiers(): void
    {
        $tiers = $this->service->createStandardTiers(
            vendorId: 'VENDOR-001',
            currency: 'USD',
        );

        $this->assertIsArray($tiers);
        $this->assertNotEmpty($tiers);

        // Should have multiple tiers with increasing thresholds
        $previousMax = 0.0;
        foreach ($tiers as $tier) {
            $this->assertInstanceOf(VolumeDiscountTierData::class, $tier);
            $this->assertEquals('VENDOR-001', $tier->vendorId);
            $this->assertGreaterThanOrEqual($previousMax, $tier->minimumSpend->getAmount());
            $previousMax = $tier->minimumSpend->getAmount();
        }
    }

    #[Test]
    public function it_calculates_discount_for_matching_tier(): void
    {
        $tiers = $this->createTestTiers();

        $result = $this->service->calculateDiscount(
            invoiceAmount: Money::of(10000.00, 'USD'),
            cumulativeSpend: Money::of(30000.00, 'USD'),
            tiers: $tiers,
        );

        $this->assertInstanceOf(VolumeDiscountResult::class, $result);
        $this->assertTrue($result->discountApplied);
        $this->assertEquals('Silver', $result->appliedTierName);
        $this->assertEquals(2.0, $result->discountPercentage);
        $this->assertEquals(200.00, $result->discountAmount->getAmount()); // 2% of 10000
    }

    #[Test]
    public function it_returns_zero_discount_when_below_first_tier(): void
    {
        $tiers = $this->createTestTiers();

        $result = $this->service->calculateDiscount(
            invoiceAmount: Money::of(5000.00, 'USD'),
            cumulativeSpend: Money::of(5000.00, 'USD'),
            tiers: $tiers,
        );

        $this->assertFalse($result->discountApplied);
        $this->assertEquals('Bronze', $result->appliedTierName);
        $this->assertEquals(0.0, $result->discountPercentage);
        $this->assertEquals(0.00, $result->discountAmount->getAmount());
    }

    #[Test]
    public function it_calculates_highest_tier_discount(): void
    {
        $tiers = $this->createTestTiers();

        $result = $this->service->calculateDiscount(
            invoiceAmount: Money::of(50000.00, 'USD'),
            cumulativeSpend: Money::of(150000.00, 'USD'),
            tiers: $tiers,
        );

        $this->assertTrue($result->discountApplied);
        $this->assertEquals('Gold', $result->appliedTierName);
        $this->assertEquals(5.0, $result->discountPercentage);
        $this->assertEquals(2500.00, $result->discountAmount->getAmount()); // 5% of 50000
    }

    #[Test]
    public function it_calculates_cumulative_discount_with_new_purchase(): void
    {
        $tiers = $this->createTestTiers();

        // Current spend is 9000 (Bronze tier, no discount)
        // New invoice is 5000, making total 14000 (Silver tier, 2% discount)
        $result = $this->service->calculateCumulativeDiscount(
            invoiceAmount: Money::of(5000.00, 'USD'),
            currentCumulativeSpend: Money::of(9000.00, 'USD'),
            tiers: $tiers,
        );

        // After this purchase, cumulative spend = 14000 → Silver tier
        $this->assertEquals('Silver', $result->appliedTierName);
        $this->assertEquals(2.0, $result->discountPercentage);

        // Discount applies to new purchase
        $this->assertEquals(100.00, $result->discountAmount->getAmount()); // 2% of 5000
    }

    #[Test]
    public function it_analyzes_tier_progression(): void
    {
        $tiers = $this->createTestTiers();

        $analysis = $this->service->analyzeTierProgression(
            currentSpend: Money::of(8000.00, 'USD'),
            tiers: $tiers,
        );

        $this->assertArrayHasKey('current_tier', $analysis);
        $this->assertArrayHasKey('next_tier', $analysis);
        $this->assertArrayHasKey('amount_to_next_tier', $analysis);
        $this->assertArrayHasKey('current_discount_rate', $analysis);
        $this->assertArrayHasKey('next_discount_rate', $analysis);
        $this->assertArrayHasKey('potential_additional_savings', $analysis);

        // Current spend of 8000 is in Bronze tier (0-10000)
        $this->assertEquals('Bronze', $analysis['current_tier']);
        $this->assertEquals('Silver', $analysis['next_tier']);
        $this->assertEquals(2000.00, $analysis['amount_to_next_tier']->getAmount()); // Need 2000 more to reach Silver
    }

    #[Test]
    public function it_identifies_optimal_tier_for_spend_amount(): void
    {
        $tiers = $this->createTestTiers();

        // If spending 25000 total
        $optimal = $this->service->findOptimalTier(
            plannedSpend: Money::of(25000.00, 'USD'),
            tiers: $tiers,
        );

        $this->assertInstanceOf(VolumeDiscountTierData::class, $optimal);
        $this->assertEquals('Silver', $optimal->tierName);
    }

    #[Test]
    public function it_handles_empty_tier_list(): void
    {
        $result = $this->service->calculateDiscount(
            invoiceAmount: Money::of(10000.00, 'USD'),
            cumulativeSpend: Money::of(50000.00, 'USD'),
            tiers: [],
        );

        $this->assertFalse($result->discountApplied);
        $this->assertEquals(0.0, $result->discountPercentage);
        $this->assertEquals(0.00, $result->discountAmount->getAmount());
    }

    #[Test]
    #[DataProvider('tierBoundaryProvider')]
    public function it_handles_tier_boundaries_correctly(
        float $cumulativeSpend,
        string $expectedTier,
        float $expectedDiscount,
    ): void {
        $tiers = $this->createTestTiers();

        $result = $this->service->calculateDiscount(
            invoiceAmount: Money::of(1000.00, 'USD'),
            cumulativeSpend: Money::of($cumulativeSpend, 'USD'),
            tiers: $tiers,
        );

        $this->assertEquals($expectedTier, $result->appliedTierName);
        $this->assertEquals($expectedDiscount, $result->discountPercentage);
    }

    public static function tierBoundaryProvider(): array
    {
        return [
            'exactly at Bronze minimum' => [0.00, 'Bronze', 0.0],
            'just below Silver threshold' => [9999.99, 'Bronze', 0.0],
            'exactly at Silver minimum' => [10000.01, 'Silver', 2.0],
            'just below Gold threshold' => [49999.99, 'Silver', 2.0],
            'exactly at Gold minimum' => [50000.01, 'Gold', 5.0],
            'well above Gold' => [100000.00, 'Gold', 5.0],
        ];
    }

    #[Test]
    public function it_suggests_additional_spend_to_maximize_discount(): void
    {
        $tiers = $this->createTestTiers();

        // Current spend 45000 (Silver), close to Gold (50000)
        $suggestion = $this->service->analyzeTierProgression(
            currentSpend: Money::of(45000.00, 'USD'),
            tiers: $tiers,
        );

        // Need 5000.01 more to reach Gold tier
        $amountToNext = $suggestion['amount_to_next_tier'];
        $this->assertLessThanOrEqual(5001.00, $amountToNext->getAmount());
        $this->assertGreaterThan(5000.00, $amountToNext->getAmount());

        // Potential savings on next tier
        $this->assertGreaterThan(
            $suggestion['current_discount_rate'],
            $suggestion['next_discount_rate']
        );
    }

    #[Test]
    public function it_calculates_period_based_cumulative_discount(): void
    {
        $tiers = $this->createTestTiers();

        // Previous purchases in period
        $previousPurchases = [
            Money::of(3000.00, 'USD'),
            Money::of(4000.00, 'USD'),
            Money::of(5000.00, 'USD'),
        ];

        $cumulativeSpend = Money::of(0.0, 'USD');
        foreach ($previousPurchases as $purchase) {
            $cumulativeSpend = Money::of(
                $cumulativeSpend->getAmount() + $purchase->getAmount(),
                'USD'
            );
        }

        // New purchase
        $newPurchase = Money::of(3000.00, 'USD');

        $result = $this->service->calculateCumulativeDiscount(
            invoiceAmount: $newPurchase,
            currentCumulativeSpend: $cumulativeSpend,
            tiers: $tiers,
        );

        // Cumulative: 12000 + 3000 = 15000 → Silver tier (2%)
        $this->assertEquals('Silver', $result->appliedTierName);
        $this->assertEquals(60.00, $result->discountAmount->getAmount()); // 2% of 3000
    }

    #[Test]
    public function it_includes_next_tier_info_in_result(): void
    {
        $tiers = $this->createTestTiers();

        $result = $this->service->calculateDiscount(
            invoiceAmount: Money::of(5000.00, 'USD'),
            cumulativeSpend: Money::of(20000.00, 'USD'),
            tiers: $tiers,
        );

        // Currently in Silver tier
        $this->assertEquals('Silver', $result->appliedTierName);

        // Should include info about next tier (Gold)
        $this->assertNotNull($result->nextTierName);
        $this->assertEquals('Gold', $result->nextTierName);
        $this->assertNotNull($result->amountToNextTier);
        // Need 50000.01 - 20000 = 30000.01 to reach Gold
        $this->assertGreaterThan(30000.00, $result->amountToNextTier->getAmount());
    }

    #[Test]
    public function it_returns_null_next_tier_when_at_highest(): void
    {
        $tiers = $this->createTestTiers();

        $result = $this->service->calculateDiscount(
            invoiceAmount: Money::of(10000.00, 'USD'),
            cumulativeSpend: Money::of(100000.00, 'USD'),
            tiers: $tiers,
        );

        // Already at Gold (highest) tier
        $this->assertEquals('Gold', $result->appliedTierName);
        $this->assertNull($result->nextTierName);
        $this->assertNull($result->amountToNextTier);
    }

    #[Test]
    public function it_calculates_effective_price_after_discount(): void
    {
        $tiers = $this->createTestTiers();

        $invoiceAmount = Money::of(10000.00, 'USD');

        $result = $this->service->calculateDiscount(
            invoiceAmount: $invoiceAmount,
            cumulativeSpend: Money::of(60000.00, 'USD'),
            tiers: $tiers,
        );

        // Gold tier: 5% discount
        $expectedEffectiveAmount = 9500.00; // 10000 - 500
        $effectiveAmount = $invoiceAmount->getAmount() - $result->discountAmount->getAmount();

        $this->assertEquals($expectedEffectiveAmount, $effectiveAmount);
    }

    /**
     * Create test tiers for testing.
     *
     * @return array<VolumeDiscountTierData>
     */
    private function createTestTiers(): array
    {
        $now = new \DateTimeImmutable();
        $nextYear = new \DateTimeImmutable('+1 year');

        return [
            new VolumeDiscountTierData(
                tierId: 'TIER-BRONZE',
                vendorId: 'VENDOR-001',
                tierName: 'Bronze',
                minimumSpend: Money::of(0.00, 'USD'),
                maximumSpend: Money::of(10000.00, 'USD'),
                discountPercentage: 0.0,
                effectiveFrom: $now,
                effectiveTo: $nextYear,
            ),
            new VolumeDiscountTierData(
                tierId: 'TIER-SILVER',
                vendorId: 'VENDOR-001',
                tierName: 'Silver',
                minimumSpend: Money::of(10000.01, 'USD'),
                maximumSpend: Money::of(50000.00, 'USD'),
                discountPercentage: 2.0,
                effectiveFrom: $now,
                effectiveTo: $nextYear,
            ),
            new VolumeDiscountTierData(
                tierId: 'TIER-GOLD',
                vendorId: 'VENDOR-001',
                tierName: 'Gold',
                minimumSpend: Money::of(50000.01, 'USD'),
                maximumSpend: null, // No upper limit
                discountPercentage: 5.0,
                effectiveFrom: $now,
                effectiveTo: $nextYear,
            ),
        ];
    }
}
