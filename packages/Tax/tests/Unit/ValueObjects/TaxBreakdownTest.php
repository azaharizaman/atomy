<?php

declare(strict_types=1);

namespace Nexus\Tax\Tests\Unit\ValueObjects;

use Nexus\Currency\ValueObjects\Money;
use Nexus\Tax\Enums\TaxLevel;
use Nexus\Tax\Enums\TaxType;
use Nexus\Tax\ValueObjects\TaxBreakdown;
use Nexus\Tax\ValueObjects\TaxLine;
use Nexus\Tax\ValueObjects\TaxRate;
use PHPUnit\Framework\TestCase;

final class TaxBreakdownTest extends TestCase
{
    public function test_it_can_be_created_with_valid_data(): void
    {
        $netAmount = Money::of('100.00', 'USD');
        $taxAmount = Money::of('7.25', 'USD');
        $grossAmount = Money::of('107.25', 'USD');

        $rate = new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '7.25',
            type: TaxType::SalesTax,
            level: TaxLevel::State,
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $taxLine = new TaxLine(
            rate: $rate,
            taxableBase: $netAmount,
            amount: $taxAmount,
            description: 'California Sales Tax',
        );

        $breakdown = new TaxBreakdown(
            netAmount: $netAmount,
            totalTaxAmount: $taxAmount,
            grossAmount: $grossAmount,
            taxLines: [$taxLine],
        );

        $this->assertSame('100.00', $breakdown->netAmount->getAmount());
        $this->assertSame('7.25', $breakdown->totalTaxAmount->getAmount());
        $this->assertSame('107.25', $breakdown->grossAmount->getAmount());
        $this->assertCount(1, $breakdown->taxLines);
        $this->assertFalse($breakdown->isReverseCharge);
    }

    public function test_it_validates_currency_consistency(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tax line currency (EUR) does not match net amount currency (USD)');

        $netAmount = Money::of('100.00', 'USD');
        $taxAmount = Money::of('7.25', 'EUR'); // Different currency!
        $grossAmount = Money::of('107.25', 'USD');

        $rate = new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '7.25',
            type: TaxType::SalesTax,
            level: TaxLevel::State,
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $taxLine = new TaxLine(
            rate: $rate,
            taxableBase: $netAmount,
            amount: $taxAmount,
            description: 'California Sales Tax',
        );

        new TaxBreakdown(
            netAmount: $netAmount,
            totalTaxAmount: $taxAmount,
            grossAmount: $grossAmount,
            taxLines: [$taxLine],
        );
    }

    public function test_it_validates_gross_amount_equals_net_plus_tax(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Gross amount (110.00) does not equal net + tax (107.25)');

        $netAmount = Money::of('100.00', 'USD');
        $taxAmount = Money::of('7.25', 'USD');
        $grossAmount = Money::of('110.00', 'USD'); // Incorrect!

        $rate = new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '7.25',
            type: TaxType::SalesTax,
            level: TaxLevel::State,
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $taxLine = new TaxLine(
            rate: $rate,
            taxableBase: $netAmount,
            amount: $taxAmount,
            description: 'California Sales Tax',
        );

        new TaxBreakdown(
            netAmount: $netAmount,
            totalTaxAmount: $taxAmount,
            grossAmount: $grossAmount,
            taxLines: [$taxLine],
        );
    }

    public function test_it_validates_total_tax_matches_sum_of_lines(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Total tax amount (10.00) does not match sum of tax lines (7.25)');

        $netAmount = Money::of('100.00', 'USD');
        $taxAmount = Money::of('10.00', 'USD'); // Incorrect total!
        $grossAmount = Money::of('110.00', 'USD');

        $rate = new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '7.25',
            type: TaxType::SalesTax,
            level: TaxLevel::State,
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $taxLine = new TaxLine(
            rate: $rate,
            taxableBase: $netAmount,
            amount: Money::of('7.25', 'USD'),
            description: 'California Sales Tax',
        );

        new TaxBreakdown(
            netAmount: $netAmount,
            totalTaxAmount: $taxAmount,
            grossAmount: $grossAmount,
            taxLines: [$taxLine],
        );
    }

    public function test_it_calculates_effective_tax_rate(): void
    {
        $netAmount = Money::of('100.00', 'USD');
        $taxAmount = Money::of('7.25', 'USD');
        $grossAmount = Money::of('107.25', 'USD');

        $rate = new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '7.25',
            type: TaxType::SalesTax,
            level: TaxLevel::State,
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $taxLine = new TaxLine(
            rate: $rate,
            taxableBase: $netAmount,
            amount: $taxAmount,
            description: 'California Sales Tax',
        );

        $breakdown = new TaxBreakdown(
            netAmount: $netAmount,
            totalTaxAmount: $taxAmount,
            grossAmount: $grossAmount,
            taxLines: [$taxLine],
        );

        $this->assertSame('7.2500', $breakdown->getEffectiveTaxRate());
    }

    public function test_it_handles_reverse_charge_flag(): void
    {
        $netAmount = Money::of('100.00', 'USD');
        $taxAmount = Money::of('0.00', 'USD');
        $grossAmount = Money::of('100.00', 'USD');

        $breakdown = new TaxBreakdown(
            netAmount: $netAmount,
            totalTaxAmount: $taxAmount,
            grossAmount: $grossAmount,
            taxLines: [],
            isReverseCharge: true,
        );

        $this->assertTrue($breakdown->isReverseCharge);
        $this->assertSame('0.00', $breakdown->totalTaxAmount->getAmount());
    }
}
