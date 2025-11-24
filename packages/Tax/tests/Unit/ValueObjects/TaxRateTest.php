<?php

declare(strict_types=1);

namespace Nexus\Tax\Tests\Unit\ValueObjects;

use Nexus\Currency\ValueObjects\Money;
use Nexus\Tax\ValueObjects\TaxRate;
use PHPUnit\Framework\TestCase;

final class TaxRateTest extends TestCase
{
    public function test_it_can_be_created_with_valid_data(): void
    {
        $rate = new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '7.25',
            type: 'sales_tax',
            level: 'state',
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $this->assertSame('US-CA-SALES', $rate->taxCode);
        $this->assertSame('7.25', $rate->rate);
        $this->assertSame('state', $rate->level);
    }

    public function test_it_rejects_negative_rate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rate must be between 0 and 100');

        new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '-5.00',
            type: 'sales_tax',
            level: 'state',
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );
    }

    public function test_it_rejects_rate_over_100_percent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rate must be between 0 and 100');

        new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '150.00',
            type: 'sales_tax',
            level: 'state',
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );
    }

    public function test_it_rejects_effective_to_before_effective_from(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('effectiveTo must be after effectiveFrom');

        new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '7.25',
            type: 'sales_tax',
            level: 'state',
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-06-01'),
            effectiveTo: new \DateTimeImmutable('2024-01-01'), // Before effectiveFrom
        );
    }

    public function test_is_effective_on_returns_true_when_date_in_range(): void
    {
        $rate = new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '7.25',
            type: 'sales_tax',
            level: 'state',
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
            effectiveTo: new \DateTimeImmutable('2024-12-31'),
        );

        $this->assertTrue($rate->isEffectiveOn(new \DateTimeImmutable('2024-06-15')));
    }

    public function test_is_effective_on_returns_false_when_date_before_range(): void
    {
        $rate = new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '7.25',
            type: 'sales_tax',
            level: 'state',
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $this->assertFalse($rate->isEffectiveOn(new \DateTimeImmutable('2023-12-31')));
    }

    public function test_is_effective_on_returns_false_when_date_after_range(): void
    {
        $rate = new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '7.25',
            type: 'sales_tax',
            level: 'state',
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
            effectiveTo: new \DateTimeImmutable('2024-12-31'),
        );

        $this->assertFalse($rate->isEffectiveOn(new \DateTimeImmutable('2025-01-01')));
    }

    public function test_calculate_tax_amount_uses_bcmath_precision(): void
    {
        $rate = new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '7.25',
            type: 'sales_tax',
            level: 'state',
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $baseAmount = Money::of('100.00', 'USD');
        $taxAmount = $rate->calculateTaxAmount($baseAmount);

        $this->assertSame('7.2500', $taxAmount->getAmount());
        $this->assertSame('USD', $taxAmount->getCurrency());
    }

    public function test_get_percentage_returns_rate_as_float(): void
    {
        $rate = new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '7.25',
            type: 'sales_tax',
            level: 'state',
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $this->assertSame(7.25, $rate->getPercentage());
    }

    public function test_is_zero_rate_returns_true_for_zero(): void
    {
        $rate = new TaxRate(
            taxCode: 'US-CA-EXEMPT',
            rate: '0.00',
            type: 'sales_tax',
            level: 'state',
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $this->assertTrue($rate->isZeroRate());
    }

    public function test_is_zero_rate_returns_false_for_nonzero(): void
    {
        $rate = new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '7.25',
            type: 'sales_tax',
            level: 'state',
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $this->assertFalse($rate->isZeroRate());
    }

    public function test_supersede_creates_new_rate_with_effective_to_set(): void
    {
        $oldRate = new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '7.25',
            type: 'sales_tax',
            level: 'state',
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
        );

        $newRate = new TaxRate(
            taxCode: 'US-CA-SALES',
            rate: '7.50',
            type: 'sales_tax',
            level: 'state',
            jurisdictionCode: 'US-CA',
            effectiveFrom: new \DateTimeImmutable('2024-07-01'),
        );

        $superseded = $oldRate->supersede($newRate);

        $this->assertNotSame($oldRate, $superseded);
        $this->assertInstanceOf(\DateTimeImmutable::class, $superseded->effectiveTo);
        $this->assertSame('2024-06-30', $superseded->effectiveTo?->format('Y-m-d'));
    }
}
