<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Financial\EarlyPaymentDiscountData;
use Nexus\ProcurementOperations\DTOs\Financial\VolumeDiscountTierData;
use Nexus\ProcurementOperations\Events\Financial\EarlyPaymentDiscountCapturedEvent;
use Nexus\ProcurementOperations\Events\Financial\EarlyPaymentDiscountMissedEvent;
use Nexus\ProcurementOperations\Services\EarlyPaymentDiscountService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;

#[CoversClass(EarlyPaymentDiscountService::class)]
final class EarlyPaymentDiscountServiceTest extends TestCase
{
    private EarlyPaymentDiscountService $service;
    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->service = new EarlyPaymentDiscountService(
            eventDispatcher: $this->eventDispatcher,
            logger: new NullLogger(),
        );
    }

    #[Test]
    public function it_creates_early_payment_discount_data(): void
    {
        $invoiceAmount = Money::of(10000.00, 'USD');
        $dueDate = new \DateTimeImmutable('+30 days');

        $discount = $this->service->getEarlyPaymentDiscount(
            invoiceId: 'INV-001',
            vendorId: 'VENDOR-001',
            invoiceAmount: $invoiceAmount,
            invoiceDate: new \DateTimeImmutable(),
            dueDate: $dueDate,
            paymentTerms: '2/10 Net 30',
            discountPercentage: 2.0,
            discountDays: 10,
        );

        $this->assertInstanceOf(EarlyPaymentDiscountData::class, $discount);
        $this->assertEquals('INV-001', $discount->invoiceId);
        $this->assertEquals('VENDOR-001', $discount->vendorId);
        $this->assertEquals(10000.00, $discount->invoiceAmount->getAmount());
        $this->assertEquals(2.0, $discount->discountPercentage);
        $this->assertEquals(10, $discount->discountDays);
        $this->assertTrue($discount->isAvailable);
    }

    #[Test]
    public function it_calculates_correct_discount_amount(): void
    {
        $invoiceAmount = Money::of(10000.00, 'USD');

        $discountAmount = $this->service->calculateEarlyPaymentDiscountAmount(
            invoiceAmount: $invoiceAmount,
            discountPercentage: 2.0,
        );

        $this->assertEquals(200.00, $discountAmount->getAmount());
        $this->assertEquals('USD', $discountAmount->getCurrency());
    }

    #[Test]
    public function it_detects_when_discount_is_still_available(): void
    {
        // Discount deadline is in the future (within 10 days)
        $discountDeadline = new \DateTimeImmutable('+5 days');

        $isAvailable = $this->service->isEarlyPaymentDiscountAvailable(
            discountDeadline: $discountDeadline,
        );

        $this->assertTrue($isAvailable);
    }

    #[Test]
    public function it_detects_when_discount_has_expired(): void
    {
        // Discount deadline has passed
        $discountDeadline = new \DateTimeImmutable('-1 day');

        $isAvailable = $this->service->isEarlyPaymentDiscountAvailable(
            discountDeadline: $discountDeadline,
        );

        $this->assertFalse($isAvailable);
    }

    #[Test]
    #[DataProvider('annualizedReturnProvider')]
    public function it_calculates_annualized_return_rate(
        float $discountPercentage,
        int $daysEarly,
        float $expectedMinReturn,
        float $expectedMaxReturn,
    ): void {
        $annualizedReturn = $this->service->calculateAnnualizedReturnRate(
            discountPercentage: $discountPercentage,
            daysEarly: $daysEarly,
        );

        // Allow for floating point variance
        $this->assertGreaterThanOrEqual($expectedMinReturn, $annualizedReturn);
        $this->assertLessThanOrEqual($expectedMaxReturn, $annualizedReturn);
    }

    public static function annualizedReturnProvider(): array
    {
        return [
            '2% discount for 20 days early' => [2.0, 20, 35.0, 40.0],  // ~36.5% annually
            '1% discount for 15 days early' => [1.0, 15, 23.0, 27.0],  // ~24.3% annually
            '3% discount for 30 days early' => [3.0, 30, 35.0, 40.0],  // ~36.5% annually
        ];
    }

    #[Test]
    public function it_creates_volume_discount_tiers(): void
    {
        $tiers = $this->service->getVolumeDiscountTiers('VENDOR-001');

        $this->assertIsArray($tiers);
        $this->assertNotEmpty($tiers);

        foreach ($tiers as $tier) {
            $this->assertInstanceOf(VolumeDiscountTierData::class, $tier);
            $this->assertEquals('VENDOR-001', $tier->vendorId);
        }
    }

    #[Test]
    public function it_calculates_volume_discount(): void
    {
        $tiers = [
            new VolumeDiscountTierData(
                tierId: 'TIER-1',
                vendorId: 'VENDOR-001',
                tierName: 'Bronze',
                minimumSpend: Money::of(0.00, 'USD'),
                maximumSpend: Money::of(10000.00, 'USD'),
                discountPercentage: 0.0,
                effectiveFrom: new \DateTimeImmutable('-30 days'),
                effectiveTo: new \DateTimeImmutable('+365 days'),
            ),
            new VolumeDiscountTierData(
                tierId: 'TIER-2',
                vendorId: 'VENDOR-001',
                tierName: 'Silver',
                minimumSpend: Money::of(10000.01, 'USD'),
                maximumSpend: Money::of(50000.00, 'USD'),
                discountPercentage: 2.0,
                effectiveFrom: new \DateTimeImmutable('-30 days'),
                effectiveTo: new \DateTimeImmutable('+365 days'),
            ),
            new VolumeDiscountTierData(
                tierId: 'TIER-3',
                vendorId: 'VENDOR-001',
                tierName: 'Gold',
                minimumSpend: Money::of(50000.01, 'USD'),
                maximumSpend: null,
                discountPercentage: 5.0,
                effectiveFrom: new \DateTimeImmutable('-30 days'),
                effectiveTo: new \DateTimeImmutable('+365 days'),
            ),
        ];

        $invoiceAmount = Money::of(25000.00, 'USD');
        $cumulativeSpend = Money::of(30000.00, 'USD');

        $result = $this->service->calculateVolumeDiscount(
            vendorId: 'VENDOR-001',
            invoiceAmount: $invoiceAmount,
            cumulativeSpendYtd: $cumulativeSpend,
            tiers: $tiers,
        );

        $this->assertEquals('Silver', $result->appliedTierName);
        $this->assertEquals(2.0, $result->discountPercentage);
        $this->assertEquals(500.00, $result->discountAmount->getAmount()); // 2% of 25000
    }

    #[Test]
    public function it_estimates_potential_discount_savings(): void
    {
        $invoiceAmounts = [
            Money::of(5000.00, 'USD'),
            Money::of(3000.00, 'USD'),
            Money::of(2000.00, 'USD'),
        ];

        $estimate = $this->service->estimatePotentialDiscountSavings(
            vendorId: 'VENDOR-001',
            invoiceAmounts: $invoiceAmounts,
            discountPercentage: 2.0,
        );

        // Total: 10000, 2% discount = 200
        $this->assertEquals(200.00, $estimate['total_potential_savings']->getAmount());
        $this->assertEquals(10000.00, $estimate['total_invoice_amount']->getAmount());
        $this->assertCount(3, $estimate['invoice_savings']);
    }

    #[Test]
    public function it_dispatches_event_when_discount_is_captured(): void
    {
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(EarlyPaymentDiscountCapturedEvent::class));

        $this->service->recordCapturedDiscount(
            invoiceId: 'INV-001',
            vendorId: 'VENDOR-001',
            invoiceAmount: Money::of(10000.00, 'USD'),
            discountAmount: Money::of(200.00, 'USD'),
            paymentDate: new \DateTimeImmutable(),
            originalDueDate: new \DateTimeImmutable('+30 days'),
            userId: 'USER-001',
        );
    }

    #[Test]
    public function it_dispatches_event_when_discount_is_missed(): void
    {
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(EarlyPaymentDiscountMissedEvent::class));

        $this->service->recordMissedDiscount(
            invoiceId: 'INV-001',
            vendorId: 'VENDOR-001',
            invoiceAmount: Money::of(10000.00, 'USD'),
            missedDiscountAmount: Money::of(200.00, 'USD'),
            discountDeadline: new \DateTimeImmutable('-1 day'),
            reason: 'Approval delay',
        );
    }

    #[Test]
    public function it_gets_discount_performance_metrics(): void
    {
        $startDate = new \DateTimeImmutable('-30 days');
        $endDate = new \DateTimeImmutable();

        $metrics = $this->service->getDiscountPerformanceMetrics(
            vendorId: 'VENDOR-001',
            startDate: $startDate,
            endDate: $endDate,
        );

        $this->assertArrayHasKey('vendor_id', $metrics);
        $this->assertArrayHasKey('total_discounts_available', $metrics);
        $this->assertArrayHasKey('discounts_captured', $metrics);
        $this->assertArrayHasKey('discounts_missed', $metrics);
        $this->assertArrayHasKey('capture_rate', $metrics);
        $this->assertArrayHasKey('total_savings', $metrics);
        $this->assertArrayHasKey('missed_savings', $metrics);
    }

    #[Test]
    public function it_prioritizes_invoices_for_discount_capture(): void
    {
        $invoices = [
            [
                'invoice_id' => 'INV-001',
                'vendor_id' => 'VENDOR-001',
                'amount' => Money::of(5000.00, 'USD'),
                'discount_deadline' => new \DateTimeImmutable('+5 days'),
                'discount_percentage' => 2.0,
            ],
            [
                'invoice_id' => 'INV-002',
                'vendor_id' => 'VENDOR-002',
                'amount' => Money::of(10000.00, 'USD'),
                'discount_deadline' => new \DateTimeImmutable('+3 days'),
                'discount_percentage' => 1.5,
            ],
            [
                'invoice_id' => 'INV-003',
                'vendor_id' => 'VENDOR-001',
                'amount' => Money::of(8000.00, 'USD'),
                'discount_deadline' => new \DateTimeImmutable('+7 days'),
                'discount_percentage' => 3.0,
            ],
        ];

        $prioritized = $this->service->prioritizeInvoicesForDiscountCapture(
            invoices: $invoices,
            availableCash: Money::of(15000.00, 'USD'),
        );

        $this->assertIsArray($prioritized);
        $this->assertNotEmpty($prioritized);

        // Should prioritize by ROI and deadline
        foreach ($prioritized as $invoice) {
            $this->assertArrayHasKey('invoice_id', $invoice);
            $this->assertArrayHasKey('priority_score', $invoice);
            $this->assertArrayHasKey('annualized_return', $invoice);
        }
    }

    #[Test]
    public function it_handles_zero_discount_percentage(): void
    {
        $invoiceAmount = Money::of(10000.00, 'USD');

        $discountAmount = $this->service->calculateEarlyPaymentDiscountAmount(
            invoiceAmount: $invoiceAmount,
            discountPercentage: 0.0,
        );

        $this->assertEquals(0.00, $discountAmount->getAmount());
    }

    #[Test]
    public function it_returns_recommendation_for_high_roi_discounts(): void
    {
        $discount = $this->service->getEarlyPaymentDiscount(
            invoiceId: 'INV-001',
            vendorId: 'VENDOR-001',
            invoiceAmount: Money::of(50000.00, 'USD'),
            invoiceDate: new \DateTimeImmutable(),
            dueDate: new \DateTimeImmutable('+30 days'),
            paymentTerms: '2/10 Net 30',
            discountPercentage: 2.0,
            discountDays: 10,
        );

        // 2% for 20 days early = ~36% annualized return
        // Should recommend capturing
        $this->assertTrue($discount->isAvailable);
        $this->assertEquals(1000.00, $discount->discountAmount->getAmount());
    }
}
