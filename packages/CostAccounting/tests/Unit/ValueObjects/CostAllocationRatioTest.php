<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Tests\Unit\ValueObjects;

use Nexus\CostAccounting\ValueObjects\CostAllocationRatio;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CostAllocationRatio Value Object
 * 
 * @covers \Nexus\CostAccounting\ValueObjects\CostAllocationRatio
 */
final class CostAllocationRatioTest extends TestCase
{
    public function testCreateWithValidRatios(): void
    {
        $ratios = [
            'cc_1' => 0.5,
            'cc_2' => 0.5,
        ];
        
        $ratio = new CostAllocationRatio($ratios);
        
        self::assertSame([0.5, 0.5], $ratio->getRatios());
        self::assertSame(['cc_1', 'cc_2'], $ratio->getCostCenterIds());
    }

    public function testCreateWithSingleCostCenter(): void
    {
        $ratios = [
            'cc_1' => 1.0,
        ];
        
        $ratio = new CostAllocationRatio($ratios);
        
        self::assertSame([1.0], $ratio->getRatios());
        self::assertCount(1, $ratio->getCostCenterIds());
    }

    public function testCreateWithThreeCostCenters(): void
    {
        $ratios = [
            'cc_1' => 0.4,
            'cc_2' => 0.35,
            'cc_3' => 0.25,
        ];
        
        $ratio = new CostAllocationRatio($ratios);
        
        self::assertCount(3, $ratio->getCostCenterIds());
    }

    public function testEmptyRatiosThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Allocation ratios cannot be empty');
        
        new CostAllocationRatio([]);
    }

    public function testNegativeRatioThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be between 0 and 1');
        
        new CostAllocationRatio([
            'cc_1' => -0.1,
            'cc_2' => 1.1,
        ]);
    }

    public function testRatioGreaterThanOneThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be between 0 and 1');
        
        new CostAllocationRatio([
            'cc_1' => 1.5,
        ]);
    }

    public function testRatiosNotSumToOneThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must sum to 1.0');
        
        new CostAllocationRatio([
            'cc_1' => 0.3,
            'cc_2' => 0.3,
        ]);
    }

    public function testGetRatioForCostCenter(): void
    {
        $ratios = [
            'cc_1' => 0.6,
            'cc_2' => 0.4,
        ];
        
        $ratio = new CostAllocationRatio($ratios);
        
        self::assertSame(0.6, $ratio->getRatioForCostCenter('cc_1'));
        self::assertSame(0.4, $ratio->getRatioForCostCenter('cc_2'));
    }

    public function testGetRatioForUnknownCostCenterThrowsException(): void
    {
        $ratios = [
            'cc_1' => 1.0,
        ];
        
        $ratio = new CostAllocationRatio($ratios);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not found in allocation ratios');
        
        $ratio->getRatioForCostCenter('unknown');
    }

    public function testCalculateAllocation(): void
    {
        $ratios = [
            'cc_1' => 0.6,
            'cc_2' => 0.4,
        ];
        
        $ratio = new CostAllocationRatio($ratios);
        $result = $ratio->calculateAllocation(1000.00);
        
        self::assertSame(600.00, $result['cc_1']);
        self::assertSame(400.00, $result['cc_2']);
    }

    public function testCalculateAllocationWithZero(): void
    {
        $ratios = [
            'cc_1' => 0.5,
            'cc_2' => 0.5,
        ];
        
        $ratio = new CostAllocationRatio($ratios);
        $result = $ratio->calculateAllocation(0);
        
        self::assertSame(0.0, $result['cc_1']);
        self::assertSame(0.0, $result['cc_2']);
    }

    public function testIsValidReturnsTrue(): void
    {
        $ratios = [
            'cc_1' => 0.5,
            'cc_2' => 0.5,
        ];
        
        $ratio = new CostAllocationRatio($ratios);
        
        self::assertTrue($ratio->isValid());
    }

    public function testCount(): void
    {
        $ratios = [
            'cc_1' => 0.4,
            'cc_2' => 0.35,
            'cc_3' => 0.25,
        ];
        
        $ratio = new CostAllocationRatio($ratios);
        
        self::assertSame(3, $ratio->count());
    }
}
