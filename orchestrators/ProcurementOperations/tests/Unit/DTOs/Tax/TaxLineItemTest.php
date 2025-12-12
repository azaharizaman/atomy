<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\DTOs\Tax;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Tax\TaxLineItem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TaxLineItem::class)]
final class TaxLineItemTest extends TestCase
{
    #[Test]
    public function standard_creates_standard_tax_line(): void
    {
        $lineItem = TaxLineItem::standard(
            lineNumber: 1,
            description: 'Office supplies',
            netAmount: Money::of(100, 'MYR'),
            taxCode: 'STD',
            taxRate: 6.0,
            taxAmount: Money::of(6, 'MYR'),
        );

        $this->assertEquals(1, $lineItem->lineNumber);
        $this->assertEquals('Office supplies', $lineItem->description);
        $this->assertEquals(100_00, $lineItem->netAmount->getAmountInCents());
        $this->assertEquals('STD', $lineItem->taxCode);
        $this->assertEquals(6.0, $lineItem->taxRate);
        $this->assertEquals(6_00, $lineItem->taxAmount->getAmountInCents());
        $this->assertFalse($lineItem->isExempt);
        $this->assertFalse($lineItem->isReverseCharge);
    }

    #[Test]
    public function vat_creates_vat_tax_line(): void
    {
        $lineItem = TaxLineItem::vat(
            lineNumber: 1,
            description: 'Software license',
            netAmount: Money::of(500, 'EUR'),
            taxCode: 'VAT20',
            taxRate: 20.0,
            taxAmount: Money::of(100, 'EUR'),
        );

        $this->assertEquals('VAT20', $lineItem->taxCode);
        $this->assertEquals(20.0, $lineItem->taxRate);
        $this->assertFalse($lineItem->isExempt);
        $this->assertFalse($lineItem->isReverseCharge);
    }

    #[Test]
    public function reverseCharge_creates_reverse_charge_line(): void
    {
        $lineItem = TaxLineItem::reverseCharge(
            lineNumber: 1,
            description: 'Cloud services',
            netAmount: Money::of(1000, 'USD'),
            taxCode: 'RC',
            taxRate: 6.0,
            taxAmount: Money::of(60, 'USD'),
        );

        $this->assertTrue($lineItem->isReverseCharge);
        $this->assertFalse($lineItem->isExempt);
        $this->assertEquals('RC', $lineItem->taxCode);
    }

    #[Test]
    public function exempt_creates_exempt_line(): void
    {
        $lineItem = TaxLineItem::exempt(
            lineNumber: 1,
            description: 'Medical equipment',
            netAmount: Money::of(2000, 'MYR'),
            exemptionCode: 'MED',
            exemptionReason: 'Medical supplies exemption under Schedule A',
        );

        $this->assertTrue($lineItem->isExempt);
        $this->assertFalse($lineItem->isReverseCharge);
        $this->assertEquals(0.0, $lineItem->taxRate);
        $this->assertEquals(0, $lineItem->taxAmount->getAmountInCents());
        $this->assertEquals('MED', $lineItem->exemptionCode);
        $this->assertStringContainsString('Medical', $lineItem->exemptionReason);
    }

    #[Test]
    public function getGrossAmount_returns_net_plus_tax(): void
    {
        $lineItem = TaxLineItem::standard(
            lineNumber: 1,
            description: 'Test item',
            netAmount: Money::of(100, 'MYR'),
            taxCode: 'STD',
            taxRate: 6.0,
            taxAmount: Money::of(6, 'MYR'),
        );

        $gross = $lineItem->getGrossAmount();

        $this->assertEquals(106_00, $gross->getAmountInCents());
    }

    #[Test]
    #[DataProvider('calculationValidationProvider')]
    public function isCalculationCorrect_validates_tax_calculation(
        float $netAmountValue,
        float $taxRate,
        float $taxAmountValue,
        float $tolerance,
        bool $expectedResult,
    ): void {
        $lineItem = TaxLineItem::standard(
            lineNumber: 1,
            description: 'Test item',
            netAmount: Money::of($netAmountValue, 'MYR'),
            taxCode: 'STD',
            taxRate: $taxRate,
            taxAmount: Money::of($taxAmountValue, 'MYR'),
        );

        $result = $lineItem->isCalculationCorrect($tolerance);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return iterable<array{float, float, float, float, bool}>
     */
    public static function calculationValidationProvider(): iterable
    {
        // Correct calculations
        yield 'Exact calculation' => [100.0, 6.0, 6.0, 0.01, true];
        yield '10% on 500' => [500.0, 10.0, 50.0, 0.01, true];
        yield '20% on 1000' => [1000.0, 20.0, 200.0, 0.01, true];

        // Within tolerance
        yield 'Within 1% tolerance' => [100.0, 6.0, 6.05, 0.01, true];

        // Outside tolerance
        yield 'Outside 1% tolerance' => [100.0, 6.0, 7.0, 0.01, false];
        yield 'Way off' => [100.0, 6.0, 10.0, 0.01, false];
    }

    #[Test]
    public function getExpectedTaxAmount_calculates_correctly(): void
    {
        $lineItem = TaxLineItem::standard(
            lineNumber: 1,
            description: 'Test item',
            netAmount: Money::of(100, 'MYR'),
            taxCode: 'STD',
            taxRate: 6.0,
            taxAmount: Money::of(5, 'MYR'), // Wrong amount intentionally
        );

        $expected = $lineItem->getExpectedTaxAmount();

        $this->assertEquals(6_00, $expected->getAmountInCents());
    }

    #[Test]
    public function getVariance_returns_difference(): void
    {
        $lineItem = TaxLineItem::standard(
            lineNumber: 1,
            description: 'Test item',
            netAmount: Money::of(100, 'MYR'),
            taxCode: 'STD',
            taxRate: 6.0,
            taxAmount: Money::of(8, 'MYR'), // 2 MYR over
        );

        $variance = $lineItem->getVariance();

        // Expected: 6, Actual: 8, Variance: 2 (over-charged)
        $this->assertEquals(2_00, $variance->getAmountInCents());
    }

    #[Test]
    public function exempt_lines_always_have_correct_calculation(): void
    {
        $lineItem = TaxLineItem::exempt(
            lineNumber: 1,
            description: 'Medical equipment',
            netAmount: Money::of(2000, 'MYR'),
            exemptionCode: 'MED',
            exemptionReason: 'Medical supplies exemption',
        );

        $this->assertTrue($lineItem->isCalculationCorrect(0.0));
        $this->assertEquals(0, $lineItem->getExpectedTaxAmount()->getAmountInCents());
    }

    #[Test]
    public function toArray_returns_serializable_data(): void
    {
        $lineItem = TaxLineItem::standard(
            lineNumber: 1,
            description: 'Office supplies',
            netAmount: Money::of(100, 'MYR'),
            taxCode: 'STD',
            taxRate: 6.0,
            taxAmount: Money::of(6, 'MYR'),
        );

        $array = $lineItem->toArray();

        $this->assertArrayHasKey('line_number', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('net_amount', $array);
        $this->assertArrayHasKey('tax_code', $array);
        $this->assertArrayHasKey('tax_rate', $array);
        $this->assertArrayHasKey('tax_amount', $array);
        $this->assertArrayHasKey('is_exempt', $array);
        $this->assertArrayHasKey('is_reverse_charge', $array);
    }
}
