<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\DTOs\Tax;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Tax\WithholdingTaxCalculation;
use Nexus\ProcurementOperations\DTOs\Tax\WithholdingTaxComponent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(WithholdingTaxCalculation::class)]
final class WithholdingTaxCalculationTest extends TestCase
{
    #[Test]
    public function noWithholding_creates_zero_withholding_result(): void
    {
        $grossAmount = Money::of(10000, 'MYR');

        $result = WithholdingTaxCalculation::noWithholding(
            grossAmount: $grossAmount,
            reason: 'Domestic vendor not subject to withholding',
        );

        $this->assertFalse($result->hasWithholding);
        $this->assertEquals(1000000, $result->grossAmount->getAmountInCents());
        $this->assertEquals(0, $result->withholdingAmount->getAmountInCents());
        $this->assertEquals(1000000, $result->netPayable->getAmountInCents());
        $this->assertEquals(0.0, $result->effectiveRate);
        $this->assertStringContainsString('Domestic', $result->reason);
    }

    #[Test]
    public function withWithholding_creates_withholding_result(): void
    {
        $grossAmount = Money::of(10000, 'MYR');
        $withholdingAmount = Money::of(1500, 'MYR');
        $netPayable = Money::of(8500, 'MYR');

        $components = [
            WithholdingTaxComponent::serviceFee(
                rate: 15.0,
                amount: $withholdingAmount,
                authority: 'LHDN',
            ),
        ];

        $result = WithholdingTaxCalculation::withWithholding(
            grossAmount: $grossAmount,
            withholdingAmount: $withholdingAmount,
            netPayable: $netPayable,
            rate: 15.0,
            components: $components,
        );

        $this->assertTrue($result->hasWithholding);
        $this->assertEquals(1000000, $result->grossAmount->getAmountInCents());
        $this->assertEquals(150000, $result->withholdingAmount->getAmountInCents());
        $this->assertEquals(850000, $result->netPayable->getAmountInCents());
        $this->assertEquals(15.0, $result->effectiveRate);
        $this->assertCount(1, $result->components);
    }

    #[Test]
    public function withTreatyRate_creates_treaty_rate_result(): void
    {
        $grossAmount = Money::of(10000, 'MYR');
        $withholdingAmount = Money::of(1000, 'MYR');
        $netPayable = Money::of(9000, 'MYR');

        $result = WithholdingTaxCalculation::withTreatyRate(
            grossAmount: $grossAmount,
            withholdingAmount: $withholdingAmount,
            netPayable: $netPayable,
            standardRate: 15.0,
            treatyRate: 10.0,
            treatyCountry: 'Singapore',
            components: [],
        );

        $this->assertTrue($result->hasWithholding);
        $this->assertTrue($result->isTreatyRate);
        $this->assertEquals(10.0, $result->effectiveRate);
        $this->assertEquals(15.0, $result->standardRate);
        $this->assertEquals('Singapore', $result->treatyCountry);
    }

    #[Test]
    public function withWithholding_validates_amounts(): void
    {
        $grossAmount = Money::of(10000, 'MYR');
        $withholdingAmount = Money::of(1500, 'MYR');
        $netPayable = Money::of(8500, 'MYR');

        $result = WithholdingTaxCalculation::withWithholding(
            grossAmount: $grossAmount,
            withholdingAmount: $withholdingAmount,
            netPayable: $netPayable,
            rate: 15.0,
            components: [],
        );

        // gross = withholding + netPayable
        $expectedNet = $grossAmount->getAmountInCents() - $withholdingAmount->getAmountInCents();
        $this->assertEquals($expectedNet, $result->netPayable->getAmountInCents());
    }

    #[Test]
    public function getEffectiveTaxSavings_calculates_treaty_benefit(): void
    {
        $grossAmount = Money::of(10000, 'MYR');
        $withholdingAmount = Money::of(1000, 'MYR'); // 10% with treaty
        $netPayable = Money::of(9000, 'MYR');

        $result = WithholdingTaxCalculation::withTreatyRate(
            grossAmount: $grossAmount,
            withholdingAmount: $withholdingAmount,
            netPayable: $netPayable,
            standardRate: 15.0,
            treatyRate: 10.0,
            treatyCountry: 'Singapore',
            components: [],
        );

        // Without treaty: 15% of 10000 = 1500
        // With treaty: 10% of 10000 = 1000
        // Savings: 500
        $savings = $result->getEffectiveTaxSavings();

        $this->assertEquals(500_00, $savings->getAmountInCents());
    }

    #[Test]
    public function toArray_returns_serializable_data(): void
    {
        $result = WithholdingTaxCalculation::withWithholding(
            grossAmount: Money::of(10000, 'MYR'),
            withholdingAmount: Money::of(1500, 'MYR'),
            netPayable: Money::of(8500, 'MYR'),
            rate: 15.0,
            components: [],
        );

        $array = $result->toArray();

        $this->assertArrayHasKey('has_withholding', $array);
        $this->assertArrayHasKey('gross_amount', $array);
        $this->assertArrayHasKey('withholding_amount', $array);
        $this->assertArrayHasKey('net_payable', $array);
        $this->assertArrayHasKey('effective_rate', $array);
        $this->assertTrue($array['has_withholding']);
    }
}
