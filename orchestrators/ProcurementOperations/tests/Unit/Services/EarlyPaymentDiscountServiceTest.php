<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Financial\EarlyPaymentDiscountData;
use Nexus\ProcurementOperations\DTOs\Financial\VolumeDiscountResult;
use Nexus\ProcurementOperations\Services\EarlyPaymentDiscountService;
use PHPUnit\Framework\Attributes\CoversClass;
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
    public function it_returns_null_for_get_early_payment_discount(): void
    {
        $discount = $this->service->getEarlyPaymentDiscount('tenant-1', 'vendor-1', 'inv-1');
        $this->assertNull($discount);
    }

    #[Test]
    public function it_calculates_early_payment_discount_amount(): void
    {
        $discountData = EarlyPaymentDiscountData::custom(
            vendorId: 'vendor-1',
            invoiceId: 'inv-1',
            invoiceAmount: Money::of(1000, 'USD'),
            discountPercentage: 2.0,
            discountDays: 10,
            netDays: 30,
            invoiceDate: new \DateTimeImmutable('2024-01-01')
        );

        $amount = $this->service->calculateEarlyPaymentDiscountAmount(
            $discountData,
            Money::of(1000, 'USD'),
            new \DateTimeImmutable('2024-01-05')
        );

        $this->assertEquals(20, $amount->getAmount());

        // Test expired
        $expiredAmount = $this->service->calculateEarlyPaymentDiscountAmount(
            $discountData,
            Money::of(1000, 'USD'),
            new \DateTimeImmutable('2024-01-15')
        );
        $this->assertEquals(0, $expiredAmount->getAmount());
    }

    #[Test]
    public function it_checks_if_early_payment_discount_is_available(): void
    {
        $discountData = EarlyPaymentDiscountData::custom(
            vendorId: 'vendor-1',
            invoiceId: 'inv-1',
            invoiceAmount: Money::of(1000, 'USD'),
            discountPercentage: 2.0,
            discountDays: 10,
            netDays: 30,
            invoiceDate: new \DateTimeImmutable('2024-01-01')
        );

        $this->assertTrue($this->service->isEarlyPaymentDiscountAvailable($discountData, new \DateTimeImmutable('2024-01-05')));
        $this->assertFalse($this->service->isEarlyPaymentDiscountAvailable($discountData, new \DateTimeImmutable('2024-01-15')));
    }

    #[Test]
    public function it_gets_days_to_discount_deadline(): void
    {
        $discountData = EarlyPaymentDiscountData::custom(
            vendorId: 'vendor-1',
            invoiceId: 'inv-1',
            invoiceAmount: Money::of(1000, 'USD'),
            discountPercentage: 2.0,
            discountDays: 10,
            netDays: 30,
            invoiceDate: new \DateTimeImmutable('2024-01-01')
        );

        // 2024-01-01 + 10 days = 2024-01-11.
        // Diff between Jan 5 and Jan 11 is 6 days.
        $this->assertEquals(6, $this->service->getDaysToDiscountDeadline($discountData, new \DateTimeImmutable('2024-01-05')));
    }

    #[Test]
    public function it_calculates_annualized_return_rate(): void
    {
        $discountData = EarlyPaymentDiscountData::custom(
            vendorId: 'vendor-1',
            invoiceId: 'inv-1',
            invoiceAmount: Money::of(1000, 'USD'),
            discountPercentage: 2.0,
            discountDays: 10,
            netDays: 30,
            invoiceDate: new \DateTimeImmutable('2024-01-01')
        );

        $rate = $this->service->calculateAnnualizedReturnRate($discountData);
        $this->assertGreaterThan(0, $rate);
    }

    #[Test]
    public function it_returns_empty_volume_discount_tiers(): void
    {
        $this->assertSame([], $this->service->getVolumeDiscountTiers('t1', 'v1'));
    }

    #[Test]
    public function it_returns_no_discount_for_calculate_volume_discount(): void
    {
        // Positional arguments: string $tenantId, string $vendorId, Money $purchaseAmount
        $result = $this->service->calculateVolumeDiscount(
            't1',
            'v1',
            Money::of(100, 'USD')
        );
        $this->assertInstanceOf(VolumeDiscountResult::class, $result);
        $this->assertFalse($result->hasDiscount);
    }

    #[Test]
    public function it_returns_zero_ytd_purchase_total(): void
    {
        $total = $this->service->getYtdPurchaseTotal('t1', 'v1');
        $this->assertEquals(0, $total->getAmount());
    }

    #[Test]
    public function it_returns_empty_potential_discount_savings(): void
    {
        $result = $this->service->estimatePotentialDiscountSavings('t1');
        $this->assertEquals(0, $result['total_potential_savings']->getAmount());
        $this->assertSame(0, $result['invoice_count']);
    }

    #[Test]
    public function it_records_captured_and_missed_discounts(): void
    {
        // These are currently just logging/void methods in the implementation
        $this->service->recordCapturedDiscount('t1', 'inv-1', Money::of(10, 'USD'), new \DateTimeImmutable());
        $this->service->recordMissedDiscount('t1', 'inv-1', Money::of(10, 'USD'), 'Too late');
        $this->assertTrue(true); // Assert no exception
    }

    #[Test]
    public function it_returns_empty_performance_metrics(): void
    {
        $now = new \DateTimeImmutable();
        $result = $this->service->getDiscountPerformanceMetrics('t1', $now, $now);
        $this->assertEquals(0, $result['total_discounts_captured']->getAmount());
    }

    #[Test]
    public function it_returns_empty_prioritized_invoices(): void
    {
        $this->assertSame([], $this->service->prioritizeInvoicesForDiscountCapture('t1', Money::of(1000, 'USD')));
    }
}
