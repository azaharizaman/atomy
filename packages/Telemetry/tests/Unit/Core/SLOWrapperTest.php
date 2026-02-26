<?php

declare(strict_types=1);

namespace Nexus\Telemetry\Tests\Unit\Core;

use Nexus\Telemetry\Contracts\TelemetryTrackerInterface;
use Nexus\Telemetry\Core\SLOWrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SLOWrapper::class)]
#[Group('monitoring')]
#[Group('slo')]
final class SLOWrapperTest extends TestCase
{
    private TelemetryTrackerInterface $telemetry;

    protected function setUp(): void
    {
        $this->telemetry = $this->createMock(TelemetryTrackerInterface::class);
    }

    #[Test]
    public function it_tracks_successful_operation(): void
    {
        $wrapper = new SLOWrapper($this->telemetry, 'api.user.create', ['service' => 'user-api']);

        $expectedTags = ['service' => 'user-api', 'operation' => 'api.user.create'];

        // Track both success and total increments
        $this->telemetry->expects($this->exactly(2))
            ->method('increment')
            ->willReturnCallback(function (string $metric, float $value, array $tags) use ($expectedTags) {
                $this->assertSame(1.0, $value);
                $this->assertSame($expectedTags, $tags);
                $this->assertTrue(
                    $metric === 'slo.api.user.create.success' || $metric === 'slo.api.user.create.total'
                );
            });

        $this->telemetry->expects($this->once())
            ->method('timing')
            ->with(
                'slo.api.user.create.latency',
                $this->greaterThanOrEqual(0),
                $expectedTags
            );

        $result = $wrapper->execute(fn() => 'user-123');

        $this->assertSame('user-123', $result);
    }

    #[Test]
    public function it_tracks_failed_operation(): void
    {
        $wrapper = new SLOWrapper($this->telemetry, 'api.order.create');

        $exception = new \RuntimeException('Database connection failed');

        $this->telemetry->expects($this->exactly(2))
            ->method('increment')
            ->willReturnCallback(function (string $metric, float $value, array $tags) {
                if ($metric === 'slo.api.order.create.failure') {
                    $this->assertSame('RuntimeException', $tags['exception']);
                    $this->assertSame('server_error', $tags['error_type']);
                }
            });

        $this->telemetry->expects($this->once())
            ->method('timing')
            ->with(
                'slo.api.order.create.latency',
                $this->greaterThanOrEqual(0),
                $this->arrayHasKey('operation')
            );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database connection failed');

        $wrapper->execute(function () use ($exception) {
            throw $exception;
        });
    }

    #[Test]
    public function it_classifies_client_errors(): void
    {
        $wrapper = new SLOWrapper($this->telemetry, 'api.validation');

        $this->telemetry->expects($this->exactly(2))
            ->method('increment')
            ->willReturnCallback(function (string $metric, float $value, array $tags) {
                if ($metric === 'slo.api.validation.failure') {
                    $this->assertSame('client_error', $tags['error_type']);
                }
            });

        $this->telemetry->expects($this->once())
            ->method('timing');

        $this->expectException(\InvalidArgumentException::class);

        $wrapper->execute(function () {
            throw new \InvalidArgumentException('Invalid email format');
        });
    }

    #[Test]
    public function it_merges_additional_tags(): void
    {
        $wrapper = new SLOWrapper($this->telemetry, 'api.payment', ['service' => 'payment']);

        $expectedTags = [
            'service' => 'payment',
            'operation' => 'api.payment',
            'provider' => 'stripe',
            'amount_range' => '100-500'
        ];

        $this->telemetry->expects($this->exactly(2))
            ->method('increment')
            ->with(
                $this->logicalOr(
                    $this->equalTo('slo.api.payment.success'),
                    $this->equalTo('slo.api.payment.total')
                ),
                1.0,
                $expectedTags
            );

        $wrapper->execute(
            fn() => true,
            ['provider' => 'stripe', 'amount_range' => '100-500']
        );
    }

    #[Test]
    public function it_tracks_latency_in_milliseconds(): void
    {
        $wrapper = new SLOWrapper($this->telemetry, 'db.query');

        $this->telemetry->expects($this->once())
            ->method('timing')
            ->with(
                'slo.db.query.latency',
                $this->logicalAnd(
                    $this->greaterThanOrEqual(10),
                    $this->lessThan(50)
                ),
                $this->anything()
            );

        $this->telemetry->expects($this->atLeastOnce())
            ->method('increment');

        $wrapper->execute(function () {
            usleep(10000); // 10ms
            return true;
        });
    }

    #[Test]
    public function it_always_tracks_total_requests(): void
    {
        $wrapper = new SLOWrapper($this->telemetry, 'cache.get');

        $totalTracked = false;

        $this->telemetry->expects($this->exactly(2))
            ->method('increment')
            ->willReturnCallback(function (string $metric, float $value, array $tags) use (&$totalTracked) {
                if ($metric === 'slo.cache.get.total') {
                    $totalTracked = true;
                }
            });

        $this->telemetry->expects($this->once())
            ->method('timing');

        $wrapper->execute(fn() => 'cached-value');

        $this->assertTrue($totalTracked, 'Total requests should always be tracked');
    }

    #[Test]
    public function it_creates_wrapper_using_static_factory(): void
    {
        $wrapper = SLOWrapper::for(
            $this->telemetry,
            'api.user.login',
            ['env' => 'production']
        );

        $this->assertInstanceOf(SLOWrapper::class, $wrapper);
        $this->assertSame('api.user.login', $wrapper->getOperation());
        $this->assertSame(['env' => 'production'], $wrapper->getBaseTags());
    }

    #[Test]
    public function it_exposes_operation_name(): void
    {
        $wrapper = new SLOWrapper($this->telemetry, 'queue.process');

        $this->assertSame('queue.process', $wrapper->getOperation());
    }

    #[Test]
    public function it_exposes_base_tags(): void
    {
        $baseTags = ['service' => 'mailer', 'env' => 'staging'];
        $wrapper = new SLOWrapper($this->telemetry, 'email.send', $baseTags);

        $this->assertSame($baseTags, $wrapper->getBaseTags());
    }

    #[Test]
    public function it_preserves_return_values(): void
    {
        $wrapper = new SLOWrapper($this->telemetry, 'calculation');

        $this->telemetry->expects($this->atLeastOnce())
            ->method('increment');

        $this->telemetry->expects($this->once())
            ->method('timing');

        $result = $wrapper->execute(function () {
            return [
                'sum' => 150,
                'average' => 50,
                'count' => 3
            ];
        });

        $this->assertIsArray($result);
        $this->assertSame(150, $result['sum']);
        $this->assertSame(50, $result['average']);
        $this->assertSame(3, $result['count']);
    }

    #[Test]
    public function it_tracks_total_even_on_failure(): void
    {
        $wrapper = new SLOWrapper($this->telemetry, 'upload.file');

        $totalTracked = false;

        $this->telemetry->expects($this->exactly(2))
            ->method('increment')
            ->willReturnCallback(function (string $metric, float $value, array $tags) use (&$totalTracked) {
                if ($metric === 'slo.upload.file.total') {
                    $totalTracked = true;
                }
            });

        $this->telemetry->expects($this->once())
            ->method('timing');

        $this->expectException(\RuntimeException::class);

        try {
            $wrapper->execute(function () {
                throw new \RuntimeException('Upload failed');
            });
        } finally {
            $this->assertTrue($totalTracked, 'Total should be tracked even on failure');
        }
    }

    #[Test]
    public function it_tracks_latency_even_on_failure(): void
    {
        $wrapper = new SLOWrapper($this->telemetry, 'external.api');

        $latencyTracked = false;

        $this->telemetry->expects($this->once())
            ->method('timing')
            ->willReturnCallback(function () use (&$latencyTracked) {
                $latencyTracked = true;
            });

        $this->telemetry->expects($this->atLeastOnce())
            ->method('increment');

        $this->expectException(\RuntimeException::class);

        try {
            $wrapper->execute(function () {
                throw new \RuntimeException('API timeout');
            });
        } finally {
            $this->assertTrue($latencyTracked, 'Latency should be tracked even on failure');
        }
    }
}
