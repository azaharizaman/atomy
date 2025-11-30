<?php

declare(strict_types=1);

namespace Nexus\Tax\Tests\Unit\ValueObjects;

use Nexus\Currency\ValueObjects\Money;
use Nexus\Tax\Enums\TaxLevel;
use Nexus\Tax\Enums\TaxType;
use Nexus\Tax\ValueObjects\TaxLine;
use Nexus\Tax\ValueObjects\TaxRate;
use PHPUnit\Framework\TestCase;

final class TaxLineTest extends TestCase
{
    public function test_it_can_be_created_with_valid_data(): void
    {
        $rate = new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '7.25',
            type: TaxType::SalesTax,
            level: TaxLevel::State,
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $taxableBase = Money::of('100.00', 'USD');
        $amount = Money::of('7.25', 'USD');

        $taxLine = new TaxLine(
            rate: $rate,
            taxableBase: $taxableBase,
            amount: $amount,
            description: 'California Sales Tax',
            glAccountCode: '2200',
        );

        $this->assertSame('US-CA-SALES', $taxLine->rate->taxCode);
        $this->assertSame('100.00', $taxLine->taxableBase->getAmount());
        $this->assertSame('7.25', $taxLine->amount->getAmount());
        $this->assertSame('California Sales Tax', $taxLine->description);
        $this->assertSame('2200', $taxLine->glAccountCode);
    }

    public function test_it_validates_empty_description(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tax line description cannot be empty');

        $rate = new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '7.25',
            type: TaxType::SalesTax,
            level: TaxLevel::State,
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        new TaxLine(
            rate: $rate,
            taxableBase: Money::of('100.00', 'USD'),
            amount: Money::of('7.25', 'USD'),
            description: '',
        );
    }

    public function test_it_validates_currency_consistency(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tax amount currency (EUR) does not match taxable base currency (USD)');

        $rate = new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '7.25',
            type: TaxType::SalesTax,
            level: TaxLevel::State,
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        new TaxLine(
            rate: $rate,
            taxableBase: Money::of('100.00', 'USD'),
            amount: Money::of('7.25', 'EUR'), // Different currency!
            description: 'California Sales Tax',
        );
    }

    public function test_it_validates_amount_matches_rate_calculation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tax amount (10.00) does not match taxable base × rate (7.2500)');

        $rate = new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '7.25',
            type: TaxType::SalesTax,
            level: TaxLevel::State,
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        new TaxLine(
            rate: $rate,
            taxableBase: Money::of('100.00', 'USD'),
            amount: Money::of('10.00', 'USD'), // Incorrect amount!
            description: 'California Sales Tax',
        );
    }

    public function test_it_calculates_total_with_children(): void
    {
        $parentRate = new TaxRate(
            taxCode: 'CA-GST',
            rate: '5.00',
            type: TaxType::GST,
            level: TaxLevel::Federal,
            jurisdictionCode: 'CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $childRate = new TaxRate(
            taxCode: 'CA-BC-PST',
            rate: '7.00',
            type: TaxType::GST,
            level: TaxLevel::State,
            jurisdictionCode: 'CA-BC',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $taxableBase = Money::of('100.00', 'CAD');
        $parentAmount = Money::of('5.00', 'CAD');
        $childTaxableBase = Money::of('105.00', 'CAD'); // Tax on tax
        $childAmount = Money::of('7.35', 'CAD');

        $child = new TaxLine(
            rate: $childRate,
            taxableBase: $childTaxableBase,
            amount: $childAmount,
            description: 'BC PST on GST-inclusive amount',
        );

        $parent = new TaxLine(
            rate: $parentRate,
            taxableBase: $taxableBase,
            amount: $parentAmount,
            description: 'Federal GST',
            children: [$child],
        );

        // Total = 5.00 + 7.35 = 12.35
        $total = $parent->getTotalWithChildren();
        $this->assertSame('12.35', $total->getAmount());
    }

    public function test_it_flattens_all_children(): void
    {
        $grandchildRate = new TaxRate(
            taxCode: 'LOCAL-TAX',
            rate: '1.00',
            type: TaxType::SalesTax,
            level: TaxLevel::Municipal,
            jurisdictionCode: 'US-CA-SF',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $grandchild = new TaxLine(
            rate: $grandchildRate,
            taxableBase: Money::of('107.25', 'USD'),
            amount: Money::of('1.07', 'USD'),
            description: 'Local Tax',
        );

        $childRate = new TaxRate(
            taxCode: 'STATE-TAX',
            rate: '7.25',
            type: TaxType::SalesTax,
            level: TaxLevel::State,
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $child = new TaxLine(
            rate: $childRate,
            taxableBase: Money::of('100.00', 'USD'),
            amount: Money::of('7.25', 'USD'),
            description: 'State Tax',
            children: [$grandchild],
        );

        $parentRate = new TaxRate(
            taxCode: 'FEDERAL-TAX',
            rate: '0.00',
            type: TaxType::SalesTax,
            level: TaxLevel::Federal,
            jurisdictionCode: 'US',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $parent = new TaxLine(
            rate: $parentRate,
            taxableBase: Money::of('100.00', 'USD'),
            amount: Money::of('0.00', 'USD'),
            description: 'Federal Tax',
            children: [$child],
        );

        $allChildren = $parent->getAllChildren();
        $this->assertCount(2, $allChildren); // child + grandchild
        $this->assertSame('State Tax', $allChildren[0]->description);
        $this->assertSame('Local Tax', $allChildren[1]->description);
    }
}
