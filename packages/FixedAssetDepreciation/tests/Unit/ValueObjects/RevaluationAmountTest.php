<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\FixedAssetDepreciation\ValueObjects\RevaluationAmount;

/**
 * Test cases for RevaluationAmount value object.
 *
 * @package Nexus\FixedAssetDepreciation\Tests\Unit\ValueObjects
 */
final class RevaluationAmountTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function constructor_createsInstanceWithCorrectValues(): void
    {
        $amount = new RevaluationAmount(
            amount: 5000.00,
            currency: 'USD',
            previousValue: 10000.00,
            newValue: 15000.00,
            depreciationImpact: 1000.00
        );

        $this->assertEquals(5000.00, $amount->amount);
        $this->assertEquals('USD', $amount->currency);
        $this->assertEquals(10000.00, $amount->previousValue);
        $this->assertEquals(15000.00, $amount->newValue);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fromValues_withPositiveDifference_createsIncrement(): void
    {
        $amount = RevaluationAmount::fromValues(
            previousValue: 10000.00,
            newValue: 15000.00,
            currency: 'USD',
            depreciationImpact: 500.00
        );

        $this->assertEquals(5000.00, $amount->amount);
        $this->assertEquals('USD', $amount->currency);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fromValues_withNegativeDifference_createsDecrement(): void
    {
        $amount = RevaluationAmount::fromValues(
            previousValue: 15000.00,
            newValue: 10000.00,
            currency: 'USD',
            depreciationImpact: -500.00
        );

        $this->assertEquals(-5000.00, $amount->amount);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fromValues_withZeroDifference_createsZeroAmount(): void
    {
        $amount = RevaluationAmount::fromValues(
            previousValue: 10000.00,
            newValue: 10000.00,
            currency: 'USD'
        );

        $this->assertEquals(0.0, $amount->amount);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function createIncrement_withValidValues_createsCorrectAmount(): void
    {
        $amount = RevaluationAmount::createIncrement(
            previousCost: 10000.00,
            newCost: 15000.00,
            previousSalvageValue: 1000.00,
            newSalvageValue: 1000.00,
            previousAccumulatedDepreciation: 3000.00,
            currency: 'USD'
        );

        $this->assertEquals(7000.00, $amount->previousValue); // 10000 - 3000
        $this->assertEquals(12000.00, $amount->newValue);    // 15000 - 3000
        $this->assertEquals(5000.00, $amount->amount);      // 12000 - 7000
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function createIncrement_withChangedSalvage_calculatesDepreciationImpact(): void
    {
        $amount = RevaluationAmount::createIncrement(
            previousCost: 10000.00,
            newCost: 15000.00,
            previousSalvageValue: 1000.00,
            newSalvageValue: 2000.00,
            previousAccumulatedDepreciation: 3000.00,
            currency: 'USD'
        );

        // Depreciation impact = (15000-2000) - (10000-1000) = 13000 - 9000 = 4000
        $this->assertEquals(4000.00, $amount->depreciationImpact);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function createDecrement_withValidValues_createsCorrectAmount(): void
    {
        $amount = RevaluationAmount::createDecrement(
            previousCost: 15000.00,
            newCost: 10000.00,
            previousSalvageValue: 1000.00,
            newSalvageValue: 1000.00,
            previousAccumulatedDepreciation: 3000.00,
            currency: 'USD'
        );

        $this->assertEquals(12000.00, $amount->previousValue);
        $this->assertEquals(7000.00, $amount->newValue);
        $this->assertEquals(-5000.00, $amount->amount);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getAbsoluteAmount_returnsPositiveValue(): void
    {
        $amount = RevaluationAmount::fromValues(
            previousValue: 15000.00,
            newValue: 10000.00,
            currency: 'USD'
        );

        $this->assertEquals(5000.00, $amount->getAbsoluteAmount());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isPositive_withPositiveAmount_returnsTrue(): void
    {
        $amount = RevaluationAmount::fromValues(
            previousValue: 10000.00,
            newValue: 15000.00,
            currency: 'USD'
        );

        $this->assertTrue($amount->isPositive());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isPositive_withNegativeAmount_returnsFalse(): void
    {
        $amount = RevaluationAmount::fromValues(
            previousValue: 15000.00,
            newValue: 10000.00,
            currency: 'USD'
        );

        $this->assertFalse($amount->isPositive());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isNegative_withNegativeAmount_returnsTrue(): void
    {
        $amount = RevaluationAmount::fromValues(
            previousValue: 15000.00,
            newValue: 10000.00,
            currency: 'USD'
        );

        $this->assertTrue($amount->isNegative());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isNegative_withPositiveAmount_returnsFalse(): void
    {
        $amount = RevaluationAmount::fromValues(
            previousValue: 10000.00,
            newValue: 15000.00,
            currency: 'USD'
        );

        $this->assertFalse($amount->isNegative());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getPercentageChange_returnsCorrectPercentage(): void
    {
        $amount = RevaluationAmount::fromValues(
            previousValue: 10000.00,
            newValue: 15000.00,
            currency: 'USD'
        );

        // (15000 - 10000) / 10000 * 100 = 50%
        $this->assertEquals(50.0, $amount->getPercentageChange());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getPercentageChange_withZeroPrevious_returnsZero(): void
    {
        $amount = RevaluationAmount::fromValues(
            previousValue: 0.0,
            newValue: 10000.00,
            currency: 'USD'
        );

        $this->assertEquals(0.0, $amount->getPercentageChange());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function toArray_returnsCorrectArray(): void
    {
        $amount = new RevaluationAmount(
            amount: 5000.00,
            currency: 'USD',
            previousValue: 10000.00,
            newValue: 15000.00,
            depreciationImpact: 1000.00
        );

        $array = $amount->toArray();
        
        $this->assertArrayHasKey('amount', $array);
        $this->assertArrayHasKey('currency', $array);
        $this->assertArrayHasKey('previous_value', $array);
        $this->assertArrayHasKey('new_value', $array);
        $this->assertArrayHasKey('depreciation_impact', $array);
        $this->assertEquals(5000.00, $array['amount']);
        $this->assertEquals('USD', $array['currency']);
        $this->assertEquals(10000.00, $array['previous_value']);
        $this->assertEquals(15000.00, $array['new_value']);
        $this->assertEquals(1000.00, $array['depreciation_impact']);
    }
}
