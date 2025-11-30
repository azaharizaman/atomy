<?php

declare(strict_types=1);

namespace Nexus\Monitoring\Tests\Unit\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;
use Nexus\Monitoring\ValueObjects\Metric;
use Nexus\Monitoring\ValueObjects\MetricType;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('monitoring')]
#[Group('value-objects')]
final class MetricTest extends TestCase
{
    #[Test]
    public function it_creates_metric_with_valid_data(): void
    {
        $timestamp = new DateTimeImmutable();
        $metric = new Metric(
            name: 'api_requests_total',
            type: MetricType::COUNTER,
            value: 100.5,
            tags: ['endpoint' => '/api/users', 'method' => 'GET'],
            timestamp: $timestamp
        );

        $this->assertSame('api_requests_total', $metric->name);
        $this->assertSame(MetricType::COUNTER, $metric->type);
        $this->assertSame(100.5, $metric->value);
        $this->assertSame(['endpoint' => '/api/users', 'method' => 'GET'], $metric->tags);
        $this->assertSame($timestamp, $metric->timestamp);
        $this->assertNull($metric->traceId);
        $this->assertNull($metric->spanId);
    }

    #[Test]
    public function it_creates_metric_with_trace_context(): void
    {
        $metric = new Metric(
            name: 'request_duration_ms',
            type: MetricType::TIMING,
            value: 250.0,
            tags: [],
            timestamp: new DateTimeImmutable(),
            traceId: '4bf92f3577b34da6a3ce929d0e0e4736',
            spanId: '00f067aa0ba902b7'
        );

        $this->assertTrue($metric->hasTraceContext());
        $this->assertSame('4bf92f3577b34da6a3ce929d0e0e4736', $metric->traceId);
        $this->assertSame('00f067aa0ba902b7', $metric->spanId);
    }

    #[Test]
    public function it_adds_trace_context_fluently(): void
    {
        $metric = new Metric(
            name: 'test_metric',
            type: MetricType::GAUGE,
            value: 50.0,
            tags: [],
            timestamp: new DateTimeImmutable()
        );

        $this->assertFalse($metric->hasTraceContext());

        $withTrace = $metric->withTraceContext('trace123', 'span456');

        $this->assertFalse($metric->hasTraceContext());
        $this->assertTrue($withTrace->hasTraceContext());
        $this->assertSame('trace123', $withTrace->traceId);
        $this->assertSame('span456', $withTrace->spanId);
    }

    #[Test]
    public function it_rejects_empty_metric_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Metric name cannot be empty');

        new Metric(
            name: '',
            type: MetricType::COUNTER,
            value: 1.0,
            tags: [],
            timestamp: new DateTimeImmutable()
        );
    }

    #[Test]
    public function it_rejects_invalid_metric_name_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must contain only alphanumeric characters');

        new Metric(
            name: 'invalid metric with spaces',
            type: MetricType::COUNTER,
            value: 1.0,
            tags: [],
            timestamp: new DateTimeImmutable()
        );
    }

    #[Test]
    public function it_rejects_non_scalar_tag_values(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tag value for key "invalid" must be scalar');

        new Metric(
            name: 'test_metric',
            type: MetricType::GAUGE,
            value: 1.0,
            tags: ['invalid' => ['array', 'value']],
            timestamp: new DateTimeImmutable()
        );
    }

    #[Test]
    public function it_rejects_partial_trace_context(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Both traceId and spanId must be provided together or both null');

        new Metric(
            name: 'test_metric',
            type: MetricType::COUNTER,
            value: 1.0,
            tags: [],
            timestamp: new DateTimeImmutable(),
            traceId: 'trace123'
            // spanId missing
        );
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $timestamp = new DateTimeImmutable('2024-11-23 10:30:45.123456');
        $metric = new Metric(
            name: 'cpu_usage',
            type: MetricType::GAUGE,
            value: 75.5,
            tags: ['host' => 'server1'],
            timestamp: $timestamp,
            traceId: 'trace123',
            spanId: 'span456'
        );

        $array = $metric->toArray();

        $this->assertSame('cpu_usage', $array['name']);
        $this->assertSame('gauge', $array['type']);
        $this->assertSame(75.5, $array['value']);
        $this->assertSame(['host' => 'server1'], $array['tags']);
        $this->assertStringContainsString('2024-11-23 10:30:45', $array['timestamp']);
        $this->assertSame('trace123', $array['trace_id']);
        $this->assertSame('span456', $array['span_id']);
    }
}
