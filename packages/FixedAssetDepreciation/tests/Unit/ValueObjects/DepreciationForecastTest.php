<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationForecast;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationSchedulePeriod;
use Nexus\FixedAssetDepreciation\Enums\DepreciationStatus;

/**
 * Test cases for DepreciationForecast value object.
 *
 * @package Nexus\FixedAssetDepreciation\Tests\Unit\ValueObjects
 */
final class DepreciationForecastTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function constructor_createsInstanceWithCorrectValues(): void
    {
        $forecast = new DepreciationForecast(
            periods: [],
            totalDepreciation: 0.0,
            averageDepreciation: 0.0,
            numberOfPeriods: 0
        );

        $this->assertEquals(0, $forecast->numberOfPeriods);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_withPeriods_calculatesCorrectTotals(): void
    {
        $periods = [
            $this->createPeriod(1, 1000.00),
            $this->createPeriod(2, 1000.00),
            $this->createPeriod(3, 1000.00),
        ];

        $forecast = DepreciationForecast::create('asset_123', $periods);

        $this->assertEquals(3, $forecast->numberOfPeriods);
        $this->assertEquals(3000.00, $forecast->totalDepreciation);
        $this->assertEquals(1000.00, $forecast->averageDepreciation);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_withEmptyPeriods_returnsZeroValues(): void
    {
        $forecast = DepreciationForecast::create('asset_123', []);

        $this->assertEquals(0, $forecast->numberOfPeriods);
        $this->assertEquals(0.0, $forecast->totalDepreciation);
        $this->assertEquals(0.0, $forecast->averageDepreciation);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getIterator_allowsIteration(): void
    {
        $periods = [
            $this->createPeriod(1, 1000.00),
            $this->createPeriod(2, 1000.00),
        ];

        $forecast = DepreciationForecast::create('asset_123', $periods);

        $count = 0;
        foreach ($forecast as $period) {
            $this->assertInstanceOf(DepreciationSchedulePeriod::class, $period);
            $count++;
        }
        $this->assertEquals(2, $count);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function count_returnsNumberOfPeriods(): void
    {
        $periods = [
            $this->createPeriod(1, 1000.00),
            $this->createPeriod(2, 1000.00),
            $this->createPeriod(3, 1000.00),
        ];

        $forecast = DepreciationForecast::create('asset_123', $periods);

        $this->assertEquals(3, count($forecast));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getRemainingDepreciation_afterSomePeriods_returnsCorrectValue(): void
    {
        $periods = [
            $this->createPeriod(1, 1000.00),  // Remaining after: 9000
            $this->createPeriod(2, 1000.00),  // Remaining after: 8000
            $this->createPeriod(3, 1000.00),  // Remaining after: 7000
        ];

        $forecast = DepreciationForecast::create('asset_123', $periods);

        $this->assertEquals(7000.00, $forecast->getRemainingDepreciation());
    }

    private function createPeriod(int $periodNumber, float $depreciationAmount): DepreciationSchedulePeriod
    {
        return new DepreciationSchedulePeriod(
            id: "PERIOD-test-{$periodNumber}",
            scheduleId: 'sch_test',
            periodId: "period_{$periodNumber}",
            periodNumber: $periodNumber,
            periodStartDate: new \DateTimeImmutable(sprintf('%04d-%02d-%02d', 2024, $periodNumber, 1)),
            periodEndDate: new \DateTimeImmutable(sprintf('%04d-%02d-%02d', 2024, $periodNumber, 28)),
            depreciationAmount: $depreciationAmount,
            accumulatedDepreciation: $depreciationAmount * $periodNumber,
            bookValueAtPeriodStart: 10000.00 - ($depreciationAmount * ($periodNumber - 1)),
            bookValueAtPeriodEnd: 10000.00 - ($depreciationAmount * $periodNumber),
            status: DepreciationStatus::CALCULATED,
            depreciationId: null,
            journalEntryId: null,
            calculationDate: null,
            postingDate: null
        );
    }
}
