<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\DTOs\Financial;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Financial\EarlyPaymentDiscountData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(EarlyPaymentDiscountData::class)]
final class EarlyPaymentDiscountDataTest extends TestCase
{
    #[Test]
    public function it_creates_two_ten_net_thirty_discount(): void
    {
        $invoiceDate = new \DateTimeImmutable('2024-01-15');
        
        $discount = EarlyPaymentDiscountData::twoTenNet30(
            discountId: 'disc-001',
            tenantId: 'tenant-001',
            vendorId: 'vendor-001',
            invoiceId: 'inv-001',
            invoiceNumber: 'INV-2024-001',
            invoiceDate: $invoiceDate,
            invoiceAmount: Money::of(10000.00, 'USD'),
        );

        $this->assertSame('disc-001', $discount->discountId);
        $this->assertSame(2.0, $discount->discountPercentage);
        $this->assertSame(10, $discount->discountDays);
        $this->assertSame(30, $discount->netDays);
        $this->assertSame('2/10 Net 30', $discount->terms);
        $this->assertSame('2024-01-25', $discount->discountDeadline->format('Y-m-d'));
        $this->assertSame('2024-02-14', $discount->netDueDate->format('Y-m-d'));
        $this->assertSame(200.0, $discount->discountAmount->getAmount());
        $this->assertSame(9800.0, $discount->netPaymentAmount->getAmount());
    }

    #[Test]
    public function it_creates_one_quarter_net_forty_five_discount(): void
    {
        $invoiceDate = new \DateTimeImmutable('2024-01-15');
        
        $discount = EarlyPaymentDiscountData::oneQuarterNet45(
            discountId: 'disc-002',
            tenantId: 'tenant-001',
            vendorId: 'vendor-001',
            invoiceId: 'inv-002',
            invoiceNumber: 'INV-2024-002',
            invoiceDate: $invoiceDate,
            invoiceAmount: Money::of(8000.00, 'USD'),
        );

        $this->assertSame(0.25, $discount->discountPercentage);
        $this->assertSame(10, $discount->discountDays);
        $this->assertSame(45, $discount->netDays);
        $this->assertSame('1/4/10 Net 45', $discount->terms);
        $this->assertSame(20.0, $discount->discountAmount->getAmount()); // 0.25% of 8000
        $this->assertSame(7980.0, $discount->netPaymentAmount->getAmount());
    }

    #[Test]
    public function it_creates_custom_discount_terms(): void
    {
        $invoiceDate = new \DateTimeImmutable('2024-01-15');
        
        $discount = EarlyPaymentDiscountData::custom(
            discountId: 'disc-003',
            tenantId: 'tenant-001',
            vendorId: 'vendor-001',
            invoiceId: 'inv-003',
            invoiceNumber: 'INV-2024-003',
            invoiceDate: $invoiceDate,
            invoiceAmount: Money::of(5000.00, 'USD'),
            discountPercentage: 3.0,
            discountDays: 7,
            netDays: 60,
        );

        $this->assertSame(3.0, $discount->discountPercentage);
        $this->assertSame(7, $discount->discountDays);
        $this->assertSame(60, $discount->netDays);
        $this->assertSame('3/7 Net 60', $discount->terms);
        $this->assertSame('2024-01-22', $discount->discountDeadline->format('Y-m-d'));
        $this->assertSame('2024-03-15', $discount->netDueDate->format('Y-m-d'));
    }

    #[Test]
    public function it_checks_if_discount_is_available(): void
    {
        $invoiceDate = new \DateTimeImmutable('2024-01-15');
        
        $discount = EarlyPaymentDiscountData::twoTenNet30(
            discountId: 'disc-001',
            tenantId: 'tenant-001',
            vendorId: 'vendor-001',
            invoiceId: 'inv-001',
            invoiceNumber: 'INV-2024-001',
            invoiceDate: $invoiceDate,
            invoiceAmount: Money::of(10000.00, 'USD'),
        );

        // Deadline is 2024-01-25
        $this->assertTrue($discount->isDiscountAvailable(new \DateTimeImmutable('2024-01-20')));
        $this->assertTrue($discount->isDiscountAvailable(new \DateTimeImmutable('2024-01-25'))); // On deadline
        $this->assertFalse($discount->isDiscountAvailable(new \DateTimeImmutable('2024-01-26')));
    }

    #[Test]
    public function it_calculates_days_to_deadline(): void
    {
        $invoiceDate = new \DateTimeImmutable('2024-01-15');
        
        $discount = EarlyPaymentDiscountData::twoTenNet30(
            discountId: 'disc-001',
            tenantId: 'tenant-001',
            vendorId: 'vendor-001',
            invoiceId: 'inv-001',
            invoiceNumber: 'INV-2024-001',
            invoiceDate: $invoiceDate,
            invoiceAmount: Money::of(10000.00, 'USD'),
        );

        // Deadline is 2024-01-25
        $this->assertSame(5, $discount->getDaysToDeadline(new \DateTimeImmutable('2024-01-20')));
        $this->assertSame(0, $discount->getDaysToDeadline(new \DateTimeImmutable('2024-01-25')));
        $this->assertSame(-5, $discount->getDaysToDeadline(new \DateTimeImmutable('2024-01-30')));
    }

    #[Test]
    public function it_calculates_annualized_return_rate(): void
    {
        $invoiceDate = new \DateTimeImmutable('2024-01-15');
        
        $discount = EarlyPaymentDiscountData::twoTenNet30(
            discountId: 'disc-001',
            tenantId: 'tenant-001',
            vendorId: 'vendor-001',
            invoiceId: 'inv-001',
            invoiceNumber: 'INV-2024-001',
            invoiceDate: $invoiceDate,
            invoiceAmount: Money::of(10000.00, 'USD'),
        );

        // 2/10 Net 30 annualized: (2 / 98) × (365 / 20) = 0.3724 (37.24%)
        $annualizedRate = $discount->getAnnualizedReturnRate();
        $this->assertGreaterThan(0.36, $annualizedRate);
        $this->assertLessThan(0.38, $annualizedRate);
    }

    #[Test]
    public function it_marks_discount_as_captured(): void
    {
        $invoiceDate = new \DateTimeImmutable('2024-01-15');
        
        $discount = EarlyPaymentDiscountData::twoTenNet30(
            discountId: 'disc-001',
            tenantId: 'tenant-001',
            vendorId: 'vendor-001',
            invoiceId: 'inv-001',
            invoiceNumber: 'INV-2024-001',
            invoiceDate: $invoiceDate,
            invoiceAmount: Money::of(10000.00, 'USD'),
        );

        $this->assertFalse($discount->isCaptured);

        $captured = $discount->withCapture(
            new \DateTimeImmutable('2024-01-22'),
            'PMT-001',
        );

        $this->assertTrue($captured->isCaptured);
        $this->assertSame('2024-01-22', $captured->capturedDate->format('Y-m-d'));
        $this->assertSame('PMT-001', $captured->paymentReference);
    }

    #[Test]
    public function it_marks_discount_as_missed(): void
    {
        $invoiceDate = new \DateTimeImmutable('2024-01-15');
        
        $discount = EarlyPaymentDiscountData::twoTenNet30(
            discountId: 'disc-001',
            tenantId: 'tenant-001',
            vendorId: 'vendor-001',
            invoiceId: 'inv-001',
            invoiceNumber: 'INV-2024-001',
            invoiceDate: $invoiceDate,
            invoiceAmount: Money::of(10000.00, 'USD'),
        );

        $missed = $discount->withMissed('Approval delayed');

        $this->assertTrue($missed->isMissed);
        $this->assertSame('Approval delayed', $missed->missedReason);
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $invoiceDate = new \DateTimeImmutable('2024-01-15');
        
        $discount = EarlyPaymentDiscountData::twoTenNet30(
            discountId: 'disc-001',
            tenantId: 'tenant-001',
            vendorId: 'vendor-001',
            invoiceId: 'inv-001',
            invoiceNumber: 'INV-2024-001',
            invoiceDate: $invoiceDate,
            invoiceAmount: Money::of(10000.00, 'USD'),
        );

        $array = $discount->toArray();

        $this->assertSame('disc-001', $array['discount_id']);
        $this->assertSame('2/10 Net 30', $array['terms']);
        $this->assertSame(2.0, $array['discount_percentage']);
        $this->assertArrayHasKey('discount_amount', $array);
        $this->assertArrayHasKey('annualized_return_rate', $array);
    }

    #[Test]
    #[DataProvider('discountPercentageProvider')]
    public function it_calculates_discount_amounts_correctly(
        float $discountPercentage,
        float $invoiceAmount,
        float $expectedDiscount,
        float $expectedNet,
    ): void {
        $invoiceDate = new \DateTimeImmutable('2024-01-15');
        
        $discount = EarlyPaymentDiscountData::custom(
            discountId: 'disc-001',
            tenantId: 'tenant-001',
            vendorId: 'vendor-001',
            invoiceId: 'inv-001',
            invoiceNumber: 'INV-2024-001',
            invoiceDate: $invoiceDate,
            invoiceAmount: Money::of($invoiceAmount, 'USD'),
            discountPercentage: $discountPercentage,
            discountDays: 10,
            netDays: 30,
        );

        $this->assertSame($expectedDiscount, $discount->discountAmount->getAmount());
        $this->assertSame($expectedNet, $discount->netPaymentAmount->getAmount());
    }

    /**
     * @return array<string, array{float, float, float, float}>
     */
    public static function discountPercentageProvider(): array
    {
        return [
            '2% on $10,000' => [2.0, 10000.00, 200.0, 9800.0],
            '1% on $5,000' => [1.0, 5000.00, 50.0, 4950.0],
            '3% on $1,000' => [3.0, 1000.00, 30.0, 970.0],
            '0.5% on $20,000' => [0.5, 20000.00, 100.0, 19900.0],
        ];
    }
}
