<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Tests\Unit\Entities;

use PHPUnit\Framework\TestCase;
use Nexus\FixedAssetDepreciation\Entities\DepreciationSchedule;
use Nexus\FixedAssetDepreciation\Enums\DepreciationMethodType;
use Nexus\FixedAssetDepreciation\Enums\DepreciationStatus;
use Nexus\FixedAssetDepreciation\Enums\DepreciationType;
use Nexus\FixedAssetDepreciation\Enums\ProrateConvention;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationLife;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationSchedulePeriod;

/**
 * Test cases for DepreciationSchedule entity.
 *
 * @package Nexus\FixedAssetDepreciation\Tests\Unit\Entities
 */
final class DepreciationScheduleTest extends TestCase
{
    private readonly DepreciationSchedule $schedule;
    private readonly array $testPeriods;

    protected function setUp(): void
    {
        $this->testPeriods = [
            $this->createPeriod(1, 10000.00, 3000.00, 7000.00),
            $this->createPeriod(2, 7000.00, 5500.00, 4500.00),
            $this->createPeriod(3, 4500.00, 7500.00, 2500.00),
        ];

        $this->schedule = new DepreciationSchedule(
            id: 'sch_123',
            assetId: 'asset_456',
            tenantId: 'tenant_789',
            methodType: DepreciationMethodType::STRAIGHT_LINE,
            depreciationType: DepreciationType::BOOK,
            depreciationLife: DepreciationLife::fromYears(3, 10000.00, 1000.00),
            acquisitionDate: new \DateTimeImmutable('2024-01-01'),
            startDepreciationDate: new \DateTimeImmutable('2024-01-01'),
            endDepreciationDate: null,
            prorateConvention: ProrateConvention::FULL_MONTH,
            periods: $this->testPeriods,
            status: DepreciationStatus::CALCULATED,
            currency: 'USD',
            createdAt: new \DateTimeImmutable('2024-01-01'),
            updatedAt: null,
            closedReason: null,
            closedAt: null
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_createsNewScheduleWithCalculatedStatus(): void
    {
        $schedule = DepreciationSchedule::create(
            assetId: 'asset_001',
            tenantId: 'tenant_001',
            methodType: DepreciationMethodType::STRAIGHT_LINE,
            depreciationType: DepreciationType::BOOK,
            depreciationLife: DepreciationLife::fromYears(1, 12000.00, 1000.00),
            acquisitionDate: new \DateTimeImmutable('2024-01-01'),
            prorateConvention: ProrateConvention::FULL_MONTH,
            currency: 'USD'
        );

        $this->assertStringStartsWith('sch_', $schedule->id);
        $this->assertEquals('asset_001', $schedule->assetId);
        $this->assertEquals(DepreciationStatus::CALCULATED, $schedule->status);
        $this->assertNull($schedule->closedAt);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isActive_withCalculatedStatus_returnsTrue(): void
    {
        $this->assertTrue($this->schedule->isActive());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isActive_withReversedStatus_returnsFalse(): void
    {
        $reversedSchedule = $this->createReversedSchedule();
        $this->assertFalse($reversedSchedule->isActive());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isClosed_withoutClosedAt_returnsFalse(): void
    {
        $this->assertFalse($this->schedule->isClosed());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isClosed_withClosedAt_returnsTrue(): void
    {
        $closedSchedule = $this->schedule->close('Asset disposed');
        $this->assertTrue($closedSchedule->isClosed());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isFullyDepreciated_withFullDepreciationLastPeriod_returnsTrue(): void
    {
        $fullyDepreciatedPeriods = [
            $this->createPeriod(1, 10000.00, 3000.00, 7000.00),
            $this->createPeriod(2, 7000.00, 6000.00, 4000.00),
            $this->createPeriod(3, 4000.00, 9000.00, 0.00), // Fully depreciated
        ];

        $fullyDepreciatedSchedule = new DepreciationSchedule(
            id: 'sch_123',
            assetId: 'asset_456',
            tenantId: 'tenant_789',
            methodType: DepreciationMethodType::STRAIGHT_LINE,
            depreciationType: DepreciationType::BOOK,
            depreciationLife: DepreciationLife::fromYears(3, 10000.00, 1000.00),
            acquisitionDate: new \DateTimeImmutable('2024-01-01'),
            startDepreciationDate: new \DateTimeImmutable('2024-01-01'),
            endDepreciationDate: null,
            prorateConvention: ProrateConvention::FULL_MONTH,
            periods: $fullyDepreciatedPeriods,
            status: DepreciationStatus::CALCULATED,
            currency: 'USD',
            createdAt: new \DateTimeImmutable('2024-01-01'),
            updatedAt: null,
            closedReason: null,
            closedAt: null
        );

        $this->assertTrue($fullyDepreciatedSchedule->isFullyDepreciated());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function isFullyDepreciated_withEmptyPeriods_returnsFalse(): void
    {
        $emptySchedule = new DepreciationSchedule(
            id: 'sch_empty',
            assetId: 'asset_456',
            tenantId: 'tenant_789',
            methodType: DepreciationMethodType::STRAIGHT_LINE,
            depreciationType: DepreciationType::BOOK,
            depreciationLife: DepreciationLife::fromYears(3, 10000.00, 1000.00),
            acquisitionDate: new \DateTimeImmutable('2024-01-01'),
            startDepreciationDate: new \DateTimeImmutable('2024-01-01'),
            endDepreciationDate: null,
            prorateConvention: ProrateConvention::FULL_MONTH,
            periods: [],
            status: DepreciationStatus::CALCULATED,
            currency: 'USD',
            createdAt: new \DateTimeImmutable('2024-01-01'),
            updatedAt: null,
            closedReason: null,
            closedAt: null
        );

        $this->assertFalse($emptySchedule->isFullyDepreciated());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getTotalDepreciation_returnsCorrectSum(): void
    {
        // Periods have depreciation amounts: 3000, 2500, 2000 = 7500
        $this->assertEquals(7500.00, $this->schedule->getTotalDepreciation());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getAccumulatedDepreciation_withoutDate_returnsTotalAccumulated(): void
    {
        // Last period has accumulated depreciation of 7500
        $this->assertEquals(7500.00, $this->schedule->getAccumulatedDepreciation());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getAccumulatedDepreciation_withAsOfDate_returnsAccumulatedUpToDate(): void
    {
        // Calculate as of start of period 2 (returns accumulated through period 1)
        $asOfDate = new \DateTimeImmutable('2024-02-01');
        
        // The accumulated should be at least 3000 (first period)
        $result = $this->schedule->getAccumulatedDepreciation($asOfDate);
        $this->assertSame(3000.00, $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getCurrentBookValue_returnsCorrectValue(): void
    {
        // totalDepreciableAmount (9000) + salvageValue (1000) - accumulated (7500) = 2500
        $this->assertEquals(2500.00, $this->schedule->getCurrentBookValue());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getRemainingDepreciation_returnsCorrectValue(): void
    {
        // totalDepreciableAmount (9000) - accumulated (7500) = 1500
        $this->assertEquals(1500.00, $this->schedule->getRemainingDepreciation());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function count_returnsNumberOfPeriods(): void
    {
        $this->assertEquals(3, $this->schedule->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getIterator_allowsIteration(): void
    {
        $count = 0;
        foreach ($this->schedule as $period) {
            $this->assertInstanceOf(DepreciationSchedulePeriod::class, $period);
            $count++;
        }
        $this->assertEquals(3, $count);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getPeriod_withValidIndex_returnsPeriod(): void
    {
        $period = $this->schedule->getPeriod(0);
        $this->assertNotNull($period);
        $this->assertEquals(1, $period->periodNumber);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getPeriod_withInvalidIndex_returnsNull(): void
    {
        $period = $this->schedule->getPeriod(99);
        $this->assertNull($period);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getFirstPeriod_returnsFirstPeriod(): void
    {
        $period = $this->schedule->getFirstPeriod();
        $this->assertNotNull($period);
        $this->assertEquals(1, $period->periodNumber);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getLastPeriod_returnsLastPeriod(): void
    {
        $period = $this->schedule->getLastPeriod();
        $this->assertNotNull($period);
        $this->assertEquals(3, $period->periodNumber);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function close_setsClosedStatusAndReason(): void
    {
        $closedSchedule = $this->schedule->close('Asset disposed');
        
        $this->assertNotNull($closedSchedule->closedAt);
        $this->assertEquals('Asset disposed', $closedSchedule->closedReason);
        $this->assertNotNull($closedSchedule->updatedAt);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function withPeriods_returnsNewInstanceWithUpdatedPeriods(): void
    {
        $newPeriods = [
            $this->createPeriod(1, 10000.00, 1000.00, 9000.00),
        ];
        
        $updatedSchedule = $this->schedule->withPeriods($newPeriods);
        
        $this->assertEquals(1, $updatedSchedule->count());
        $this->assertNotNull($updatedSchedule->updatedAt);
    }

    private function createPeriod(int $periodNumber, float $openingValue, float $accumulatedDepreciation, float $closingValue): DepreciationSchedulePeriod
    {
        return new DepreciationSchedulePeriod(
            id: "PERIOD-sch_123-{$periodNumber}",
            scheduleId: 'sch_123',
            periodId: "period_{$periodNumber}",
            periodNumber: $periodNumber,
            periodStartDate: new \DateTimeImmutable(sprintf('%04d-%02d-%02d', 2024, $periodNumber, 1)),
            periodEndDate: new \DateTimeImmutable(sprintf('%04d-%02d-%02d', 2024, $periodNumber, 28)),
            depreciationAmount: $accumulatedDepreciation - ($this->getPreviousAccumulated($periodNumber)),
            accumulatedDepreciation: $accumulatedDepreciation,
            bookValueAtPeriodStart: $openingValue,
            bookValueAtPeriodEnd: $closingValue,
            status: DepreciationStatus::CALCULATED,
            depreciationId: null,
            journalEntryId: null,
            calculationDate: null,
            postingDate: null
        );
    }

    private function getPreviousAccumulated(int $periodNumber): float
    {
        $previous = [0, 0, 3000, 5500];
        return $previous[$periodNumber] ?? 0;
    }

    private function createReversedSchedule(): DepreciationSchedule
    {
        return new DepreciationSchedule(
            id: 'sch_123',
            assetId: 'asset_456',
            tenantId: 'tenant_789',
            methodType: DepreciationMethodType::STRAIGHT_LINE,
            depreciationType: DepreciationType::BOOK,
            depreciationLife: DepreciationLife::fromYears(3, 10000.00, 1000.00),
            acquisitionDate: new \DateTimeImmutable('2024-01-01'),
            startDepreciationDate: new \DateTimeImmutable('2024-01-01'),
            endDepreciationDate: null,
            prorateConvention: ProrateConvention::FULL_MONTH,
            periods: $this->testPeriods,
            status: DepreciationStatus::REVERSED,
            currency: 'USD',
            createdAt: new \DateTimeImmutable('2024-01-01'),
            updatedAt: null,
            closedReason: null,
            closedAt: null
        );
    }
}
