<?php

declare(strict_types=1);

namespace Nexus\Monitoring\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\Monitoring\Contracts\CardinalityGuardInterface;
use Nexus\Monitoring\Contracts\MetricStorageInterface;
use Nexus\Monitoring\Contracts\SamplingStrategyInterface;
use Nexus\Monitoring\Exceptions\CardinalityLimitExceededException;
use Nexus\Monitoring\Services\TelemetryTracker;
use Nexus\Monitoring\ValueObjects\Metric;
use Nexus\Monitoring\ValueObjects\MetricType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Stub interface for TenantContextInterface (not yet importing nexus/tenant).
 */
interface TenantContextInterface
{
    public function getCurrentTenantId(): ?string;
}

#[CoversClass(TelemetryTracker::class)]
#[Group('monitoring')]
#[Group('telemetry')]
final class TelemetryTrackerTest extends TestCase
{
    private MockObject&MetricStorageInterface $storage;
    private MockObject&CardinalityGuardInterface $cardinalityGuard;
    private MockObject&LoggerInterface $logger;
    
    protected function setUp(): void
    {
        $this->storage = $this->createMock(MetricStorageInterface::class);
        $this->cardinalityGuard = $this->createMock(CardinalityGuardInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }
    
    #[Test]
    public function it_records_gauge_metric_without_tenant_context(): void
    {
        $this->cardinalityGuard
            ->expects($this->once())
            ->method('validateTags')
            ->with(['environment' => 'production']);
        
        $this->storage
            ->expects($this->once())
            ->method('store')
            ->with($this->callback(function (Metric $metric) {
                return $metric->name === 'app.memory.usage'
                    && $metric->type === MetricType::GAUGE
                    && $metric->value === 1024.5
                    && $metric->tags === ['environment' => 'production']
                    && $metric->traceId === null
                    && $metric->spanId === null;
            }));
        
        $tracker = new TelemetryTracker(
            storage: $this->storage,
            cardinalityGuard: $this->cardinalityGuard,
            logger: $this->logger
        );
        
        $tracker->gauge('app.memory.usage', 1024.5, ['environment' => 'production']);
    }
    
    #[Test]
    public function it_records_gauge_metric_with_tenant_context(): void
    {
        $tenantContext = $this->createMock(TenantContextInterface::class);
        $tenantContext
            ->expects($this->once())
            ->method('getCurrentTenantId')
            ->willReturn('tenant-123');
        
        $this->cardinalityGuard
            ->expects($this->once())
            ->method('validateTags')
            ->with(['environment' => 'production', 'tenant_id' => 'tenant-123']);
        
        $this->storage
            ->expects($this->once())
            ->method('store')
            ->with($this->callback(function (Metric $metric) {
                return $metric->tags === ['environment' => 'production', 'tenant_id' => 'tenant-123'];
            }));
        
        $tracker = new TelemetryTracker(
            storage: $this->storage,
            cardinalityGuard: $this->cardinalityGuard,
            logger: $this->logger,
            tenantContext: $tenantContext
        );
        
        $tracker->gauge('app.memory.usage', 1024.5, ['environment' => 'production']);
    }
    
    #[Test]
    public function it_records_gauge_metric_with_trace_context(): void
    {
        $this->cardinalityGuard->method('validateTags');
        
        $this->storage
            ->expects($this->once())
            ->method('store')
            ->with($this->callback(function (Metric $metric) {
                return $metric->traceId === 'trace-abc123'
                    && $metric->spanId === 'span-xyz789';
            }));
        
        $tracker = new TelemetryTracker(
            storage: $this->storage,
            cardinalityGuard: $this->cardinalityGuard,
            logger: $this->logger
        );
        
        $tracker->gauge('app.memory.usage', 1024.5, [], 'trace-abc123', 'span-xyz789');
    }
    
    #[Test]
    public function it_records_counter_increment(): void
    {
        $this->cardinalityGuard->method('validateTags');
        
        $this->storage
            ->expects($this->once())
            ->method('store')
            ->with($this->callback(function (Metric $metric) {
                return $metric->name === 'http.requests'
                    && $metric->type === MetricType::COUNTER
                    && $metric->value === 1.0;
            }));
        
        $tracker = new TelemetryTracker(
            storage: $this->storage,
            cardinalityGuard: $this->cardinalityGuard,
            logger: $this->logger
        );
        
        $tracker->increment('http.requests');
    }
    
    #[Test]
    public function it_records_counter_increment_with_custom_value(): void
    {
        $this->cardinalityGuard->method('validateTags');
        
        $this->storage
            ->expects($this->once())
            ->method('store')
            ->with($this->callback(function (Metric $metric) {
                return $metric->type === MetricType::COUNTER
                    && $metric->value === 5.0;
            }));
        
        $tracker = new TelemetryTracker(
            storage: $this->storage,
            cardinalityGuard: $this->cardinalityGuard,
            logger: $this->logger
        );
        
        $tracker->increment('batch.processed', 5.0, ['type' => 'invoice']);
    }
    
    #[Test]
    public function it_records_timing_metric(): void
    {
        $this->cardinalityGuard->method('validateTags');
        
        $this->storage
            ->expects($this->once())
            ->method('store')
            ->with($this->callback(function (Metric $metric) {
                return $metric->name === 'api.response.time'
                    && $metric->type === MetricType::TIMING
                    && $metric->value === 125.5;
            }));
        
        $tracker = new TelemetryTracker(
            storage: $this->storage,
            cardinalityGuard: $this->cardinalityGuard,
            logger: $this->logger
        );
        
        $tracker->timing('api.response.time', 125.5, ['endpoint' => '/api/invoices']);
    }
    
    #[Test]
    public function it_records_histogram_metric(): void
    {
        $this->cardinalityGuard->method('validateTags');
        
        $this->storage
            ->expects($this->once())
            ->method('store')
            ->with($this->callback(function (Metric $metric) {
                return $metric->name === 'payment.amount'
                    && $metric->type === MetricType::HISTOGRAM
                    && $metric->value === 1500.75;
            }));
        
        $tracker = new TelemetryTracker(
            storage: $this->storage,
            cardinalityGuard: $this->cardinalityGuard,
            logger: $this->logger
        );
        
        $tracker->histogram('payment.amount', 1500.75, ['currency' => 'MYR']);
    }
    
    #[Test]
    public function it_throws_exception_when_cardinality_limit_exceeded(): void
    {
        $this->cardinalityGuard
            ->expects($this->once())
            ->method('validateTags')
            ->willThrowException(new CardinalityLimitExceededException('user_id', 10000));
        
        $this->storage->expects($this->never())->method('store');
        
        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Cardinality limit exceeded'),
                $this->callback(fn($context) => 
                    $context['tag_key'] === 'user_id' && $context['current_cardinality'] === 10000
                )
            );
        
        $tracker = new TelemetryTracker(
            storage: $this->storage,
            cardinalityGuard: $this->cardinalityGuard,
            logger: $this->logger
        );
        
        $this->expectException(CardinalityLimitExceededException::class);
        
        $tracker->gauge('custom.metric', 100.0, ['user_id' => 'user-999999']);
    }
    
    #[Test]
    public function it_skips_metric_when_sampling_strategy_rejects(): void
    {
        $samplingStrategy = $this->createMock(SamplingStrategyInterface::class);
        $samplingStrategy
            ->expects($this->once())
            ->method('shouldSample')
            ->willReturn(false);
        
        $this->cardinalityGuard->expects($this->never())->method('validateTags');
        $this->storage->expects($this->never())->method('store');
        
        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('Metric sampled out'));
        
        $tracker = new TelemetryTracker(
            storage: $this->storage,
            cardinalityGuard: $this->cardinalityGuard,
            logger: $this->logger,
            samplingStrategy: $samplingStrategy
        );
        
        $tracker->gauge('low.priority.metric', 50.0);
    }
    
    #[Test]
    public function it_stores_metric_when_sampling_strategy_accepts(): void
    {
        $samplingStrategy = $this->createMock(SamplingStrategyInterface::class);
        $samplingStrategy
            ->expects($this->once())
            ->method('shouldSample')
            ->willReturn(true);
        
        $this->cardinalityGuard->method('validateTags');
        $this->storage->expects($this->once())->method('store');
        
        $tracker = new TelemetryTracker(
            storage: $this->storage,
            cardinalityGuard: $this->cardinalityGuard,
            logger: $this->logger,
            samplingStrategy: $samplingStrategy
        );
        
        $tracker->gauge('sampled.metric', 50.0);
    }
    
    #[Test]
    public function it_processes_all_metrics_when_no_sampling_strategy_provided(): void
    {
        $this->cardinalityGuard->method('validateTags');
        $this->storage->expects($this->exactly(3))->method('store');
        
        $tracker = new TelemetryTracker(
            storage: $this->storage,
            cardinalityGuard: $this->cardinalityGuard,
            logger: $this->logger
        );
        
        $tracker->gauge('metric.1', 10.0);
        $tracker->increment('metric.2');
        $tracker->timing('metric.3', 100.0);
    }
    
    #[Test]
    #[DataProvider('metricMethodProvider')]
    public function it_logs_successful_metric_recording(string $method, array $args): void
    {
        $this->cardinalityGuard->method('validateTags');
        $this->storage->method('store');
        
        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with(
                $this->stringContains('Metric recorded'),
                $this->callback(fn($context) => isset($context['metric_name']))
            );
        
        $tracker = new TelemetryTracker(
            storage: $this->storage,
            cardinalityGuard: $this->cardinalityGuard,
            logger: $this->logger
        );
        
        $tracker->$method(...$args);
    }
    
    public static function metricMethodProvider(): array
    {
        return [
            'gauge' => ['gauge', ['test.gauge', 100.0, ['tag' => 'value']]],
            'increment' => ['increment', ['test.counter', 5.0, ['tag' => 'value']]],
            'timing' => ['timing', ['test.timing', 250.0, ['tag' => 'value']]],
            'histogram' => ['histogram', ['test.histogram', 1500.0, ['tag' => 'value']]],
        ];
    }
    
    #[Test]
    public function it_preserves_tag_order_when_appending_tenant_id(): void
    {
        $tenantContext = $this->createMock(TenantContextInterface::class);
        $tenantContext->method('getCurrentTenantId')->willReturn('tenant-999');
        
        $this->cardinalityGuard->method('validateTags');
        
        $this->storage
            ->expects($this->once())
            ->method('store')
            ->with($this->callback(function (Metric $metric) {
                $expectedTags = [
                    'env' => 'prod',
                    'region' => 'eu',
                    'tenant_id' => 'tenant-999',
                ];
                return $metric->tags === $expectedTags;
            }));
        
        $tracker = new TelemetryTracker(
            storage: $this->storage,
            cardinalityGuard: $this->cardinalityGuard,
            logger: $this->logger,
            tenantContext: $tenantContext
        );
        
        $tracker->gauge('test.metric', 1.0, ['env' => 'prod', 'region' => 'eu']);
    }
    
    #[Test]
    public function it_does_not_override_existing_tenant_id_tag(): void
    {
        $tenantContext = $this->createMock(TenantContextInterface::class);
        $tenantContext->expects($this->never())->method('getCurrentTenantId');
        
        $this->cardinalityGuard->method('validateTags');
        
        $this->storage
            ->expects($this->once())
            ->method('store')
            ->with($this->callback(function (Metric $metric) {
                return $metric->tags['tenant_id'] === 'manual-tenant-override';
            }));
        
        $tracker = new TelemetryTracker(
            storage: $this->storage,
            cardinalityGuard: $this->cardinalityGuard,
            logger: $this->logger,
            tenantContext: $tenantContext
        );
        
        $tracker->gauge('test.metric', 1.0, ['tenant_id' => 'manual-tenant-override']);
    }
}
