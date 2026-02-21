<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Tests\Unit\ValueObjects;

use Nexus\FixedAssetDepreciation\ValueObjects\BookValue;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationAmount;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationLife;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for depreciation value objects.
 */
final class ValueObjectsTest extends TestCase
{
    public function testBookValueCalculatesCorrectly(): void
    {
        $bookValue = new BookValue(
            cost: 10000.0,
            salvageValue: 1000.0,
            accumulatedDepreciation: 3000.0
        );

        $this->assertEquals(7000.0, $bookValue->getNetBookValue());
        $this->assertEquals(9000.0, $bookValue->getDepreciableAmount());
        $this->assertEquals(6000.0, $bookValue->getRemainingDepreciableAmount());
        $this->assertFalse($bookValue->isFullyDepreciated());
    }

    public function testBookValueDetectsFullyDepreciated(): void
    {
        $bookValue = new BookValue(
            cost: 10000.0,
            salvageValue: 1000.0,
            accumulatedDepreciation: 9000.0
        );

        $this->assertTrue($bookValue->isFullyDepreciated());
        $this->assertEquals(0.0, $bookValue->getRemainingDepreciableAmount());
    }

    public function testBookValueDepreciate(): void
    {
        $bookValue = new BookValue(
            cost: 10000.0,
            salvageValue: 1000.0,
            accumulatedDepreciation: 3000.0
        );

        $depreciationAmount = new DepreciationAmount(500.0, 'USD');
        $newBookValue = $bookValue->depreciate($depreciationAmount);

        $this->assertEquals(3500.0, $newBookValue->accumulatedDepreciation);
        $this->assertEquals(6500.0, $newBookValue->getNetBookValue());
        $this->assertNotSame($bookValue, $newBookValue);
    }

    public function testBookValueRevalue(): void
    {
        $bookValue = new BookValue(
            cost: 10000.0,
            salvageValue: 1000.0,
            accumulatedDepreciation: 3000.0
        );

        $newBookValue = $bookValue->revalue(15000.0, 2000.0);

        $this->assertEquals(15000.0, $newBookValue->cost);
        $this->assertEquals(2000.0, $newBookValue->salvageValue);
        $this->assertEquals(3000.0, $newBookValue->accumulatedDepreciation);
        $this->assertEquals(12000.0, $newBookValue->getNetBookValue());
    }

    public function testDepreciationAmountAdd(): void
    {
        $amount1 = new DepreciationAmount(100.0, 'USD', 500.0);
        $amount2 = new DepreciationAmount(50.0, 'USD', 550.0);

        $result = $amount1->add($amount2);

        $this->assertEquals(150.0, $result->amount);
        $this->assertEquals(1100.0, $result->accumulatedDepreciation);
    }

    public function testDepreciationAmountSubtract(): void
    {
        $amount1 = new DepreciationAmount(100.0, 'USD', 500.0);
        $amount2 = new DepreciationAmount(30.0, 'USD', 530.0);

        $result = $amount1->subtract($amount2);

        $this->assertEquals(70.0, $result->amount);
        $this->assertEquals(-30.0, $result->accumulatedDepreciation);
    }

    public function testDepreciationAmountMultiply(): void
    {
        $amount = new DepreciationAmount(100.0, 'USD', 500.0);

        $result = $amount->multiply(1.5);

        $this->assertEquals(150.0, $result->amount);
        $this->assertEquals(750.0, $result->accumulatedDepreciation);
    }

    public function testDepreciationAmountRejectsDifferentCurrencies(): void
    {
        $amount1 = new DepreciationAmount(100.0, 'USD');
        $amount2 = new DepreciationAmount(50.0, 'EUR');

        $this->expectException(\InvalidArgumentException::class);
        $amount1->add($amount2);
    }

    public function testDepreciationLifeCalculatesCorrectly(): void
    {
        $life = DepreciationLife::fromYears(5, 10000.0, 1000.0);

        $this->assertEquals(5, $life->usefulLifeYears);
        $this->assertEquals(60, $life->usefulLifeMonths);
        $this->assertEquals(1000.0, $life->salvageValue);
        $this->assertEquals(9000.0, $life->totalDepreciableAmount);
        $this->assertEquals(150.0, $life->getMonthlyDepreciation());
        $this->assertEquals(1800.0, $life->getAnnualDepreciation());
        $this->assertTrue($life->isValid());
    }

    public function testDepreciationLifeHandlesZeroLife(): void
    {
        $life = new DepreciationLife(
            usefulLifeYears: 0,
            usefulLifeMonths: 0,
            salvageValue: 1000.0,
            totalDepreciableAmount: 9000.0
        );

        $this->assertEquals(0.0, $life->getMonthlyDepreciation());
        $this->assertFalse($life->isValid());
    }
}
