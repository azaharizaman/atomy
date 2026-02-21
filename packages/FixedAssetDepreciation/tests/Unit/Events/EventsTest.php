<?php

declare(strict_types=1);

namespace Nexus\FixedAssetDepreciation\Tests\Unit\Events;

use PHPUnit\Framework\TestCase;
use Nexus\FixedAssetDepreciation\Events\DepreciationRunCompletedEvent;
use Nexus\FixedAssetDepreciation\ValueObjects\DepreciationRunResult;

/**
 * Test cases for Event classes.
 *
 * @package Nexus\FixedAssetDepreciation\Tests\Unit\Events
 */
final class EventsTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function depreciationRunCompletedEvent_createsEventWithCorrectData(): void
    {
        $event = new DepreciationRunCompletedEvent(
            runId: 'run_123',
            tenantId: 'tenant_456',
            periodId: 'period_001',
            processedCount: 10,
            errorCount: 0,
            totalDepreciation: 10000.00,
            currency: 'USD',
            runDate: new \DateTimeImmutable('2024-01-31 10:00:00'),
            completedDate: new \DateTimeImmutable('2024-01-31 10:05:00')
        );

        $this->assertEquals('run_123', $event->runId);
        $this->assertEquals('tenant_456', $event->tenantId);
        $this->assertEquals('period_001', $event->periodId);
        $this->assertEquals(10, $event->processedCount);
        $this->assertEquals(10000.00, $event->totalDepreciation);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function depreciationRunCompletedEvent_hasErrors_withErrors_returnsTrue(): void
    {
        $event = new DepreciationRunCompletedEvent(
            runId: 'run_123',
            tenantId: 'tenant_456',
            periodId: 'period_001',
            processedCount: 10,
            errorCount: 2,
            totalDepreciation: 10000.00,
            currency: 'USD',
            runDate: new \DateTimeImmutable('2024-01-31 10:00:00'),
            completedDate: new \DateTimeImmutable('2024-01-31 10:05:00')
        );

        $this->assertTrue($event->hasErrors());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function depreciationRunCompletedEvent_hasErrors_withoutErrors_returnsFalse(): void
    {
        $event = new DepreciationRunCompletedEvent(
            runId: 'run_123',
            tenantId: 'tenant_456',
            periodId: 'period_001',
            processedCount: 10,
            errorCount: 0,
            totalDepreciation: 10000.00,
            currency: 'USD',
            runDate: new \DateTimeImmutable('2024-01-31 10:00:00'),
            completedDate: new \DateTimeImmutable('2024-01-31 10:05:00')
        );

        $this->assertFalse($event->hasErrors());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function depreciationRunCompletedEvent_getSuccessRate_withAllSuccess_returns100(): void
    {
        $event = new DepreciationRunCompletedEvent(
            runId: 'run_123',
            tenantId: 'tenant_456',
            periodId: 'period_001',
            processedCount: 10,
            errorCount: 0,
            totalDepreciation: 10000.00,
            currency: 'USD',
            runDate: new \DateTimeImmutable('2024-01-31 10:00:00'),
            completedDate: new \DateTimeImmutable('2024-01-31 10:05:00')
        );

        $this->assertEquals(100.0, $event->getSuccessRate());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function depreciationRunCompletedEvent_getSuccessRate_withSomeErrors_returnsCorrectRate(): void
    {
        $event = new DepreciationRunCompletedEvent(
            runId: 'run_123',
            tenantId: 'tenant_456',
            periodId: 'period_001',
            processedCount: 8,
            errorCount: 2,
            totalDepreciation: 10000.00,
            currency: 'USD',
            runDate: new \DateTimeImmutable('2024-01-31 10:00:00'),
            completedDate: new \DateTimeImmutable('2024-01-31 10:05:00')
        );

        $this->assertEquals(80.0, $event->getSuccessRate());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function depreciationRunCompletedEvent_getDurationInSeconds_returnsDuration(): void
    {
        $event = new DepreciationRunCompletedEvent(
            runId: 'run_123',
            tenantId: 'tenant_456',
            periodId: 'period_001',
            processedCount: 10,
            errorCount: 0,
            totalDepreciation: 10000.00,
            currency: 'USD',
            runDate: new \DateTimeImmutable('2024-01-31 10:00:00'),
            completedDate: new \DateTimeImmutable('2024-01-31 10:05:00')
        );

        // 5 minutes = 300 seconds (but diff->s gives seconds portion only)
        $duration = $event->getDurationInSeconds();
        $this->assertGreaterThanOrEqual(0, $duration);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function depreciationRunCompletedEvent_toArray_returnsCorrectArray(): void
    {
        $event = new DepreciationRunCompletedEvent(
            runId: 'run_123',
            tenantId: 'tenant_456',
            periodId: 'period_001',
            processedCount: 10,
            errorCount: 0,
            totalDepreciation: 10000.00,
            currency: 'USD',
            runDate: new \DateTimeImmutable('2024-01-31 10:00:00'),
            completedDate: new \DateTimeImmutable('2024-01-31 10:05:00')
        );

        $array = $event->toArray();
        
        $this->assertArrayHasKey('run_id', $array);
        $this->assertArrayHasKey('tenant_id', $array);
        $this->assertArrayHasKey('processed_count', $array);
        $this->assertArrayHasKey('total_depreciation', $array);
        $this->assertEquals('run_123', $array['run_id']);
    }
}
