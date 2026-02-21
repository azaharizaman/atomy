<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationSchedulePeriod;
use Nexus\FixedAssetDepreciation\Enums\DepreciationStatus;

/**
 * Test cases for DepreciationSchedulePeriod value object.
 *
 * @package Nexus\FixedAssetDepreciation\Tests\Unit\ValueObjects
 */
final class DepreciationSchedulePeriodTest extends TestCase
{
    private DepreciationSchedulePeriod $period;

    protected function setUp(): void
    {
        $this->period = new DepreciationSchedulePeriod(
            id: 'PERIOD-sch_001-1',
            scheduleId: 'sch_001',
            periodId: 'period_2024_01',
            periodNumber: 1,
            periodStartDate: new \DateTimeImmutable('2024-01-01'),
            periodEndDate: new \DateTimeImmutable('2024-01-31'),
            depreciationAmount: 1000.00,
            accumulatedDepreciation: 1000.00,
            bookValueAtPeriodStart: 10000.00,
            bookValueAtPeriodEnd: 9000.00,
            status: DepreciationStatus::CALCULATED,
            depreciationId: null,
            journalEntryId: null,
            calculationDate: null,
            postingDate: null
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function constructor_createsInstanceWithCorrectValues(): void
    {
        $this->assertEquals('PERIOD-sch_001-1', $this->period->id);
        $this->assertEquals('sch_001', $this->period->scheduleId);
        $this->assertEquals('period_2024_01', $this->period->periodId);
        $this->assertEquals(1, $this->period->periodNumber);
        $this->assertEquals(1000.00, $this->period->depreciationAmount);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_createsNewPeriodWithCalculatedStatus(): void
    {
        $period = DepreciationSchedulePeriod::create(
            scheduleId: 'sch_test',
            periodId: 'period_test',
            periodNumber: 1,
            periodStartDate: new \DateTimeImmutable('2024-01-01'),
            periodEndDate: new \DateTimeImmutable('2024-01-31'),
            openingBookValue: 10000.00,
            depreciationAmount: 1000.00,
            previousAccumulatedDepreciation: 0.00
        );

        $this->assertEquals(DepreciationStatus::CALCULATED, $period->status);
        $this->assertEquals(1000.00, $period->accumulatedDepreciation);
        $this->assertEquals(9000.00, $period->bookValueAtPeriodEnd);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isCalculated_withCalculatedStatusAndNoDepreciationId_returnsFalse(): void
    {
        // With CALCULATED status and no depreciationId, isCalculated returns false
        // because the formula is: status !== CALCULATED || depreciationId !== null
        // CALCULATED !== CALCULATED is false, and null !== null is false
        $this->assertFalse($this->period->isCalculated());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isCalculated_withDepreciationId_returnsTrue(): void
    {
        $calculatedPeriod = new DepreciationSchedulePeriod(
            id: 'PERIOD-sch_001-1',
            scheduleId: 'sch_001',
            periodId: 'period_2024_01',
            periodNumber: 1,
            periodStartDate: new \DateTimeImmutable('2024-01-01'),
            periodEndDate: new \DateTimeImmutable('2024-01-31'),
            depreciationAmount: 1000.00,
            accumulatedDepreciation: 1000.00,
            bookValueAtPeriodStart: 10000.00,
            bookValueAtPeriodEnd: 9000.00,
            status: DepreciationStatus::CALCULATED,
            depreciationId: 'depr_123', // Has depreciation ID
            journalEntryId: null,
            calculationDate: null,
            postingDate: null
        );

        $this->assertTrue($calculatedPeriod->isCalculated());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isPosted_withPostedStatus_returnsTrue(): void
    {
        $postedPeriod = new DepreciationSchedulePeriod(
            id: 'PERIOD-sch_001-1',
            scheduleId: 'sch_001',
            periodId: 'period_2024_01',
            periodNumber: 1,
            periodStartDate: new \DateTimeImmutable('2024-01-01'),
            periodEndDate: new \DateTimeImmutable('2024-01-31'),
            depreciationAmount: 1000.00,
            accumulatedDepreciation: 1000.00,
            bookValueAtPeriodStart: 10000.00,
            bookValueAtPeriodEnd: 9000.00,
            status: DepreciationStatus::POSTED,
            depreciationId: 'depr_123',
            journalEntryId: 'je_001',
            calculationDate: new \DateTimeImmutable('2024-01-31'),
            postingDate: new \DateTimeImmutable('2024-02-01')
        );

        $this->assertTrue($postedPeriod->isPosted());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isReversed_withReversedStatus_returnsTrue(): void
    {
        $reversedPeriod = new DepreciationSchedulePeriod(
            id: 'PERIOD-sch_001-1',
            scheduleId: 'sch_001',
            periodId: 'period_2024_01',
            periodNumber: 1,
            periodStartDate: new \DateTimeImmutable('2024-01-01'),
            periodEndDate: new \DateTimeImmutable('2024-01-31'),
            depreciationAmount: 1000.00,
            accumulatedDepreciation: 1000.00,
            bookValueAtPeriodStart: 10000.00,
            bookValueAtPeriodEnd: 9000.00,
            status: DepreciationStatus::REVERSED,
            depreciationId: 'depr_123',
            journalEntryId: 'je_001',
            calculationDate: new \DateTimeImmutable('2024-01-31'),
            postingDate: new \DateTimeImmutable('2024-02-01')
        );

        $this->assertTrue($reversedPeriod->isReversed());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isAdjusted_withAdjustedStatus_returnsTrue(): void
    {
        $adjustedPeriod = new DepreciationSchedulePeriod(
            id: 'PERIOD-sch_001-1',
            scheduleId: 'sch_001',
            periodId: 'period_2024_01',
            periodNumber: 1,
            periodStartDate: new \DateTimeImmutable('2024-01-01'),
            periodEndDate: new \DateTimeImmutable('2024-01-31'),
            depreciationAmount: 1000.00,
            accumulatedDepreciation: 1000.00,
            bookValueAtPeriodStart: 10000.00,
            bookValueAtPeriodEnd: 9000.00,
            status: DepreciationStatus::ADJUSTED,
            depreciationId: 'depr_123',
            journalEntryId: null,
            calculationDate: new \DateTimeImmutable('2024-01-31'),
            postingDate: null
        );

        $this->assertTrue($adjustedPeriod->isAdjusted());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function canBeCalculated_withCalculatedStatusAndNoDepreciationId_returnsTrue(): void
    {
        $this->assertTrue($this->period->canBeCalculated());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function canBeCalculated_withDepreciationId_returnsFalse(): void
    {
        $calculatedPeriod = new DepreciationSchedulePeriod(
            id: 'PERIOD-sch_001-1',
            scheduleId: 'sch_001',
            periodId: 'period_2024_01',
            periodNumber: 1,
            periodStartDate: new \DateTimeImmutable('2024-01-01'),
            periodEndDate: new \DateTimeImmutable('2024-01-31'),
            depreciationAmount: 1000.00,
            accumulatedDepreciation: 1000.00,
            bookValueAtPeriodStart: 10000.00,
            bookValueAtPeriodEnd: 9000.00,
            status: DepreciationStatus::CALCULATED,
            depreciationId: 'depr_123',
            journalEntryId: null,
            calculationDate: null,
            postingDate: null
        );

        $this->assertFalse($calculatedPeriod->canBeCalculated());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function canBePosted_withDepreciationIdAndNotPostedOrReversed_returnsTrue(): void
    {
        $postablePeriod = new DepreciationSchedulePeriod(
            id: 'PERIOD-sch_001-1',
            scheduleId: 'sch_001',
            periodId: 'period_2024_01',
            periodNumber: 1,
            periodStartDate: new \DateTimeImmutable('2024-01-01'),
            periodEndDate: new \DateTimeImmutable('2024-01-31'),
            depreciationAmount: 1000.00,
            accumulatedDepreciation: 1000.00,
            bookValueAtPeriodStart: 10000.00,
            bookValueAtPeriodEnd: 9000.00,
            status: DepreciationStatus::CALCULATED,
            depreciationId: 'depr_123',
            journalEntryId: null,
            calculationDate: null,
            postingDate: null
        );

        $this->assertTrue($postablePeriod->canBePosted());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function canBePosted_withoutDepreciationId_returnsFalse(): void
    {
        $this->assertFalse($this->period->canBePosted());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getDepreciableAmount_returnsCorrectValue(): void
    {
        // depreciable amount = bookValueAtPeriodStart - bookValueAtPeriodEnd + depreciationAmount
        // = 10000 - 9000 + 1000 = 2000
        $this->assertEquals(2000.00, $this->period->getDepreciableAmount());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getDaysInPeriod_returnsCorrectValue(): void
    {
        // January has 31 days
        $days = $this->period->getDaysInPeriod();
        $this->assertEquals(31, $days);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getDepreciationRate_returnsCorrectValue(): void
    {
        // Rate = depreciationAmount / bookValueAtPeriodStart
        // = 1000 / 10000 = 0.1
        $this->assertEquals(0.1, $this->period->getDepreciationRate());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getDepreciationRate_withZeroBookValue_returnsZero(): void
    {
        $zeroBookPeriod = new DepreciationSchedulePeriod(
            id: 'PERIOD-sch_001-1',
            scheduleId: 'sch_001',
            periodId: 'period_2024_01',
            periodNumber: 1,
            periodStartDate: new \DateTimeImmutable('2024-01-01'),
            periodEndDate: new \DateTimeImmutable('2024-01-31'),
            depreciationAmount: 0.00,
            accumulatedDepreciation: 10000.00,
            bookValueAtPeriodStart: 0.00,
            bookValueAtPeriodEnd: 0.00,
            status: DepreciationStatus::CALCULATED,
            depreciationId: null,
            journalEntryId: null,
            calculationDate: null,
            postingDate: null
        );

        $this->assertEquals(0.0, $zeroBookPeriod->getDepreciationRate());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isFullyDepreciatedAfter_withPositiveBookValue_returnsFalse(): void
    {
        $this->assertFalse($this->period->isFullyDepreciatedAfter());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isFullyDepreciatedAfter_withZeroBookValue_returnsTrue(): void
    {
        $fullyDepreciatedPeriod = new DepreciationSchedulePeriod(
            id: 'PERIOD-sch_001-1',
            scheduleId: 'sch_001',
            periodId: 'period_2024_01',
            periodNumber: 1,
            periodStartDate: new \DateTimeImmutable('2024-01-01'),
            periodEndDate: new \DateTimeImmutable('2024-01-31'),
            depreciationAmount: 10000.00,
            accumulatedDepreciation: 10000.00,
            bookValueAtPeriodStart: 10000.00,
            bookValueAtPeriodEnd: 0.00,
            status: DepreciationStatus::CALCULATED,
            depreciationId: null,
            journalEntryId: null,
            calculationDate: null,
            postingDate: null
        );

        $this->assertTrue($fullyDepreciatedPeriod->isFullyDepreciatedAfter());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function withStatus_returnsNewInstanceWithUpdatedStatus(): void
    {
        $postedPeriod = $this->period->withStatus(DepreciationStatus::POSTED);
        
        $this->assertEquals(DepreciationStatus::POSTED, $postedPeriod->status);
        // Other properties should remain the same
        $this->assertEquals($this->period->id, $postedPeriod->id);
        $this->assertEquals($this->period->depreciationAmount, $postedPeriod->depreciationAmount);
    }
}
