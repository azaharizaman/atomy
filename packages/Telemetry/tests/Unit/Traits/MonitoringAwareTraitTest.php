<?php

declare(strict_types=1);

namespace Nexus\Telemetry\Tests\Unit\Traits;

use Nexus\Telemetry\Contracts\TelemetryTrackerInterface;
use Nexus\Telemetry\Traits\MonitoringAwareTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MonitoringAwareTrait::class)]
#[Group('monitoring')]
#[Group('traits')]
final class MonitoringAwareTraitTest extends TestCase
{
    private TelemetryTrackerInterface $telemetry;

    protected function setUp(): void
    {
        $this->telemetry = $this->createMock(TelemetryTrackerInterface::class);
    }

    #[Test]
    public function it_sets_and_gets_telemetry_instance(): void
    {
        $service = new class {
            use MonitoringAwareTrait;

            public function exposeTelemetry(): ?TelemetryTrackerInterface
            {
                return $this->getTelemetry();
            }
        };

        $this->assertNull($service->exposeTelemetry());

        $service->setTelemetry($this->telemetry);

        $this->assertSame($this->telemetry, $service->exposeTelemetry());
    }

    #[Test]
    public function it_records_gauge_metric(): void
    {
        $service = new class {
            use MonitoringAwareTrait;

            public function doRecordGauge(): void
            {
                $this->recordGauge('queue.size', 150.0, ['queue' => 'emails']);
            }
        };

        $this->telemetry->expects($this->once())
            ->method('gauge')
            ->with('queue.size', 150.0, ['queue' => 'emails']);

        $service->setTelemetry($this->telemetry);
        $service->doRecordGauge();
    }

    #[Test]
    public function it_records_increment_metric(): void
    {
        $service = new class {
            use MonitoringAwareTrait;

            public function doRecordIncrement(): void
            {
                $this->recordIncrement('api.requests', 1.0, ['endpoint' => '/users']);
            }
        };

        $this->telemetry->expects($this->once())
            ->method('increment')
            ->with('api.requests', 1.0, ['endpoint' => '/users']);

        $service->setTelemetry($this->telemetry);
        $service->doRecordIncrement();
    }

    #[Test]
    public function it_records_timing_metric(): void
    {
        $service = new class {
            use MonitoringAwareTrait;

            public function doRecordTiming(): void
            {
                $this->recordTiming('db.query', 25.5, ['table' => 'users']);
            }
        };

        $this->telemetry->expects($this->once())
            ->method('timing')
            ->with('db.query', 25.5, ['table' => 'users']);

        $service->setTelemetry($this->telemetry);
        $service->doRecordTiming();
    }

    #[Test]
    public function it_records_histogram_metric(): void
    {
        $service = new class {
            use MonitoringAwareTrait;

            public function doRecordHistogram(): void
            {
                $this->recordHistogram('response.size', 1024.0, ['format' => 'json']);
            }
        };

        $this->telemetry->expects($this->once())
            ->method('histogram')
            ->with('response.size', 1024.0, ['format' => 'json']);

        $service->setTelemetry($this->telemetry);
        $service->doRecordHistogram();
    }

    #[Test]
    public function it_tracks_operation_with_slo_wrapper(): void
    {
        $service = new class {
            use MonitoringAwareTrait;

            public function doTrackOperation(): string
            {
                return $this->trackOperation('user.create', function () {
                    return 'user-123';
                }, ['service' => 'users']);
            }
        };

        // SLOWrapper will call increment (success + total) and timing
        $this->telemetry->expects($this->exactly(2))
            ->method('increment');

        $this->telemetry->expects($this->once())
            ->method('timing');

        $service->setTelemetry($this->telemetry);
        $result = $service->doTrackOperation();

        $this->assertSame('user-123', $result);
    }

    #[Test]
    public function it_times_operation_and_records_duration(): void
    {
        $service = new class {
            use MonitoringAwareTrait;

            public function doTimeOperation(): int
            {
                return $this->timeOperation('calculation', function () {
                    usleep(10000); // 10ms
                    return 42;
                }, ['type' => 'math']);
            }
        };

        $this->telemetry->expects($this->once())
            ->method('timing')
            ->with(
                'calculation',
                $this->logicalAnd(
                    $this->greaterThanOrEqual(10),
                    $this->lessThan(50)
                ),
                ['type' => 'math']
            );

        $service->setTelemetry($this->telemetry);
        $result = $service->doTimeOperation();

        $this->assertSame(42, $result);
    }

    #[Test]
    public function it_does_not_fail_when_telemetry_not_set(): void
    {
        $service = new class {
            use MonitoringAwareTrait;

            public function doOperations(): string
            {
                $this->recordGauge('test', 1.0);
                $this->recordIncrement('test');
                $this->recordTiming('test', 10.0);
                $this->recordHistogram('test', 5.0);
                return 'success';
            }
        };

        // Should not throw exception
        $result = $service->doOperations();

        $this->assertSame('success', $result);
    }

    #[Test]
    public function it_executes_trackOperation_without_telemetry(): void
    {
        $service = new class {
            use MonitoringAwareTrait;

            public function doTrack(): string
            {
                return $this->trackOperation('test', fn() => 'result');
            }
        };

        $result = $service->doTrack();

        $this->assertSame('result', $result);
    }

    #[Test]
    public function it_executes_timeOperation_without_telemetry(): void
    {
        $service = new class {
            use MonitoringAwareTrait;

            public function doTime(): int
            {
                return $this->timeOperation('test', fn() => 100);
            }
        };

        $result = $service->doTime();

        $this->assertSame(100, $result);
    }

    #[Test]
    public function it_propagates_exceptions_from_trackOperation(): void
    {
        $service = new class {
            use MonitoringAwareTrait;

            public function doTrack(): void
            {
                $this->trackOperation('failing', function () {
                    throw new \RuntimeException('Operation failed');
                });
            }
        };

        $this->telemetry->expects($this->exactly(2))
            ->method('increment'); // failure + total

        $this->telemetry->expects($this->once())
            ->method('timing');

        $service->setTelemetry($this->telemetry);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Operation failed');

        $service->doTrack();
    }
}
