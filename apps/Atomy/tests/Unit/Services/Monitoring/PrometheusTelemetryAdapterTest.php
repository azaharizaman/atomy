<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Monitoring;

use App\Services\Monitoring\PrometheusTelemetryAdapter;
use PHPUnit\Framework\TestCase;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;

/**
 * Tests for PrometheusTelemetryAdapter
 *
 * Verifies that the adapter correctly implements TelemetryTrackerInterface
 * and properly translates universal metrics calls to Prometheus primitives.
 */
class PrometheusTelemetryAdapterTest extends TestCase
{
    private CollectorRegistry $registry;
    private PrometheusTelemetryAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Use InMemory storage for test isolation
        $this->registry = new CollectorRegistry(new InMemory());
        $this->adapter = new PrometheusTelemetryAdapter($this->registry);
    }

    /** @test */
    public function it_records_gauge_metrics(): void
    {
        // Act
        $this->adapter->gauge('memory_usage_bytes', 1024000, [
            'service' => 'eventstream',
            'tenant_id' => 'tenant-123',
        ]);

        // Assert
        $metrics = $this->registry->getMetricFamilySamples();
        
        // Filter for our metric (use first() with closure since metrics are objects)
        $ourMetric = collect($metrics)->first(fn($m) => $m->getName() === 'eventstream_memory_usage_bytes');
        
        $this->assertNotNull($ourMetric, 'Expected metric not found');
        $this->assertEquals('eventstream_memory_usage_bytes', $ourMetric->getName());
        $this->assertEquals('gauge', $ourMetric->getType());
        $this->assertEquals(1024000, $ourMetric->getSamples()[0]->getValue());
        $this->assertEquals(['service', 'tenant_id'], $ourMetric->getLabelNames());
        $this->assertEquals(['eventstream', 'tenant-123'], $ourMetric->getSamples()[0]->getLabelValues());
    }

    /** @test */
    public function it_increments_counter_metrics(): void
    {
        // Act
        $this->adapter->increment('events_appended_total', 1, [
            'stream_name' => 'account-1000',
        ]);
        
        $this->adapter->increment('events_appended_total', 5, [
            'stream_name' => 'account-1000',
        ]);

        // Assert
        $metrics = $this->registry->getMetricFamilySamples();
        
        $ourMetric = collect($metrics)->first(fn($m) => $m->getName() === 'eventstream_events_appended_total');
        
        $this->assertNotNull($ourMetric);
        $this->assertEquals('eventstream_events_appended_total', $ourMetric->getName());
        $this->assertEquals('counter', $ourMetric->getType());
        $this->assertEquals(6, $ourMetric->getSamples()[0]->getValue()); // 1 + 5
    }

    /** @test */
    public function it_records_timing_metrics_as_histograms(): void
    {
        // Act
        $this->adapter->timing('event_append_duration_ms', 45.5, [
            'stream_name' => 'account-1000',
        ]);

        // Assert
        $metrics = $this->registry->getMetricFamilySamples();
        
        $ourMetric = collect($metrics)->first(fn($m) => $m->getName() === 'eventstream_event_append_duration_ms');
        
        $this->assertNotNull($ourMetric);
        $this->assertEquals('eventstream_event_append_duration_ms', $ourMetric->getName());
        $this->assertEquals('histogram', $ourMetric->getType());
        
        // Verify histogram has sum and count
        $samples = $ourMetric->getSamples();
        $sumSample = collect($samples)->first(fn($s) => str_ends_with($s->getName(), '_sum'));
        $countSample = collect($samples)->first(fn($s) => str_ends_with($s->getName(), '_count'));
        
        $this->assertNotNull($sumSample);
        $this->assertNotNull($countSample);
        $this->assertEquals(45.5, $sumSample->getValue());
        $this->assertEquals(1, $countSample->getValue());
    }

    /** @test */
    public function it_uses_timing_optimized_buckets_for_timing_metrics(): void
    {
        // Act
        $this->adapter->timing('event_append_duration_ms', 50, []);

        // Assert
        $metrics = $this->registry->getMetricFamilySamples();
        $ourMetric = collect($metrics)->first(fn($m) => $m->getName() === 'eventstream_event_append_duration_ms');
        
        $this->assertNotNull($ourMetric);
        $samples = $ourMetric->getSamples();
        
        // Check that timing-specific buckets are used
        $bucketSamples = collect($samples)->filter(fn($s) => str_ends_with($s->getName(), '_bucket'));
        
        $this->assertGreaterThan(0, $bucketSamples->count());
        
        // Verify 50ms bucket exists
        $bucket50 = $bucketSamples->first(fn($s) => 
            in_array('50', $s->getLabelValues()) || 
            in_array(50, $s->getLabelValues())
        );
        $this->assertNotNull($bucket50);
    }

    /** @test */
    public function it_records_histogram_metrics(): void
    {
        // Act
        $this->adapter->histogram('response_size_bytes', 2048, [
            'endpoint' => '/api/events',
        ]);

        // Assert
        $metrics = $this->registry->getMetricFamilySamples();
        $ourMetric = collect($metrics)->first(fn($m) => $m->getName() === 'eventstream_response_size_bytes');
        
        $this->assertNotNull($ourMetric, 'Expected metric not found');
        $this->assertEquals('eventstream_response_size_bytes', $ourMetric->getName());
        $this->assertEquals('histogram', $ourMetric->getType());
    }

    /** @test */
    public function it_handles_metrics_without_tags(): void
    {
        // Act
        $this->adapter->increment('total_requests', 1);
        $this->adapter->gauge('active_connections', 42);
        $this->adapter->timing('request_duration_ms', 123.45);

        // Assert
        $metrics = $this->registry->getMetricFamilySamples();
        
        $counterMetric = collect($metrics)->first(fn($m) => $m->getName() === 'eventstream_total_requests');
        $gaugeMetric = collect($metrics)->first(fn($m) => $m->getName() === 'eventstream_active_connections');
        $histogramMetric = collect($metrics)->first(fn($m) => $m->getName() === 'eventstream_request_duration_ms');
        
        $this->assertNotNull($counterMetric);
        $this->assertNotNull($gaugeMetric);
        $this->assertNotNull($histogramMetric);
        
        // Only check our eventstream metrics (exclude php_info)
        $eventStreamMetrics = collect($metrics)->filter(fn($m) => str_starts_with($m->getName(), 'eventstream_'));
        
        foreach ($eventStreamMetrics as $metric) {
            $this->assertEmpty($metric->getLabelNames(), "Metric {$metric->getName()} should have no labels");
        }
    }

    /** @test */
    public function it_supports_multiple_label_combinations(): void
    {
        // Act
        $this->adapter->increment('events_total', 1, [
            'stream_name' => 'account-1000',
            'event_type' => 'AccountCredited',
        ]);
        
        $this->adapter->increment('events_total', 2, [
            'stream_name' => 'account-2000',
            'event_type' => 'AccountDebited',
        ]);

        // Assert
        $metrics = $this->registry->getMetricFamilySamples();
        $ourMetric = collect($metrics)->first(fn($m) => $m->getName() === 'eventstream_events_total');
        
        $this->assertNotNull($ourMetric);
        $this->assertCount(2, $ourMetric->getSamples()); // Two different label combinations
        
        $sample1 = $ourMetric->getSamples()[0];
        $sample2 = $ourMetric->getSamples()[1];
        
        $this->assertEquals(1, $sample1->getValue());
        $this->assertEquals(2, $sample2->getValue());
    }

    /** @test */
    public function it_exports_metrics_in_prometheus_text_format(): void
    {
        // Arrange
        $this->adapter->increment('events_appended_total', 100, ['stream_name' => 'test-stream']);
        $this->adapter->gauge('projection_lag_seconds', 5.5, ['projector' => 'balance']);
        $this->adapter->timing('event_append_duration_ms', 25.3, []);

        // Act
        $export = $this->adapter->exportPrometheusMetrics();

        // Assert
        $this->assertIsString($export);
        
        // Verify Prometheus text format structure
        $this->assertStringContainsString('# HELP eventstream_events_appended_total', $export);
        $this->assertStringContainsString('# TYPE eventstream_events_appended_total counter', $export);
        $this->assertStringContainsString('eventstream_events_appended_total{stream_name="test-stream"} 100', $export);
        
        $this->assertStringContainsString('# HELP eventstream_projection_lag_seconds', $export);
        $this->assertStringContainsString('# TYPE eventstream_projection_lag_seconds gauge', $export);
        $this->assertStringContainsString('eventstream_projection_lag_seconds{projector="balance"} 5.5', $export);
        
        $this->assertStringContainsString('# HELP eventstream_event_append_duration_ms', $export);
        $this->assertStringContainsString('# TYPE eventstream_event_append_duration_ms histogram', $export);
    }

    /** @test */
    public function it_ignores_trace_context_parameters(): void
    {
        // Trace context is part of TelemetryTrackerInterface but not used in basic Prometheus implementation
        
        // Act - should not throw exception
        $this->adapter->increment(
            'test_metric',
            1,
            ['label' => 'value'],
            'trace-id-123',
            'span-id-456'
        );

        // Assert - metric recorded normally
        $metrics = $this->registry->getMetricFamilySamples();
        $ourMetric = collect($metrics)->first(fn($m) => $m->getName() === 'eventstream_test_metric');
        $this->assertNotNull($ourMetric);
    }

    /** @test */
    public function it_handles_underscores_in_metric_names(): void
    {
        // Prometheus metric names should contain [a-zA-Z0-9:_]
        // Underscores are the most common and safest choice
        
        // Act
        $this->adapter->increment('events_appended_total', 1);
        $this->adapter->gauge('projection_lag_seconds', 5.5);
        $this->adapter->timing('event_append_duration_ms', 123.4);
        
        // Assert
        $metrics = $this->registry->getMetricFamilySamples();
        
        $counterMetric = collect($metrics)->first(fn($m) => $m->getName() === 'eventstream_events_appended_total');
        $gaugeMetric = collect($metrics)->first(fn($m) => $m->getName() === 'eventstream_projection_lag_seconds');
        $histogramMetric = collect($metrics)->first(fn($m) => $m->getName() === 'eventstream_event_append_duration_ms');
        
        $this->assertNotNull($counterMetric);
        $this->assertNotNull($gaugeMetric);
        $this->assertNotNull($histogramMetric);
    }

    /** @test */
    public function it_accumulates_metrics_across_multiple_calls(): void
    {
        // Act - Simulate tracking 10 event appends
        for ($i = 0; $i < 10; $i++) {
            $this->adapter->increment('events_appended_total', 1, ['stream_name' => 'test']);
            $this->adapter->timing('event_append_duration_ms', rand(10, 50), ['stream_name' => 'test']);
        }

        // Assert
        $metrics = $this->registry->getMetricFamilySamples();
        
        $counterMetric = collect($metrics)->first(fn($m) => $m->getName() === 'eventstream_events_appended_total');
        $histogramMetric = collect($metrics)->first(fn($m) => $m->getName() === 'eventstream_event_append_duration_ms');
        
        $this->assertNotNull($counterMetric);
        $this->assertNotNull($histogramMetric);
        
        // Counter should be 10
        $this->assertEquals(10, $counterMetric->getSamples()[0]->getValue());
        
        // Histogram count should be 10
        $countSample = collect($histogramMetric->getSamples())->first(fn($s) => str_ends_with($s->getName(), '_count'));
        $this->assertEquals(10, $countSample->getValue());
    }

    /** @test */
    public function it_namespace_all_metrics_with_eventstream_prefix(): void
    {
        // Act
        $this->adapter->increment('custom_metric', 1);
        $this->adapter->gauge('another_metric', 42);
        $this->adapter->timing('duration_metric', 100);

        // Assert
        $metrics = $this->registry->getMetricFamilySamples();
        
        // Filter for our eventstream metrics (Prometheus client adds default PHP metrics)
        $eventstreamMetrics = collect($metrics)->filter(fn($m) => str_starts_with($m->getName(), 'eventstream_'));
        
        $this->assertGreaterThanOrEqual(3, $eventstreamMetrics->count());
        
        foreach ($eventstreamMetrics as $metric) {
            $this->assertStringStartsWith('eventstream_', $metric->getName());
        }
    }

    /** @test */
    public function it_handles_empty_tag_values(): void
    {
        // Act - Empty string tag values should be allowed
        $this->adapter->increment('test_metric', 1, [
            'tag1' => '',
            'tag2' => 'value',
        ]);

        // Assert
        $metrics = $this->registry->getMetricFamilySamples();
        $ourMetric = collect($metrics)->first(fn($m) => $m->getName() === 'eventstream_test_metric');
        
        $this->assertNotNull($ourMetric);
        
        $sample = $ourMetric->getSamples()[0];
        $this->assertEquals(['', 'value'], $sample->getLabelValues());
    }

    /** @test */
    public function it_supports_fractional_counter_increments(): void
    {
        // Act
        $this->adapter->increment('bytes_processed', 1024.5, ['source' => 'api']);
        $this->adapter->increment('bytes_processed', 512.25, ['source' => 'api']);

        // Assert
        $metrics = $this->registry->getMetricFamilySamples();
        $ourMetric = collect($metrics)->first(fn($m) => $m->getName() === 'eventstream_bytes_processed');
        
        $this->assertNotNull($ourMetric);
        $sample = $ourMetric->getSamples()[0];
        
        $this->assertEquals(1536.75, $sample->getValue()); // 1024.5 + 512.25
    }

    /** @test */
    public function it_creates_separate_metrics_for_different_label_combinations(): void
    {
        // Act
        $this->adapter->increment('requests', 10, ['method' => 'GET', 'status' => '200']);
        $this->adapter->increment('requests', 5, ['method' => 'POST', 'status' => '201']);
        $this->adapter->increment('requests', 2, ['method' => 'GET', 'status' => '404']);

        // Assert
        $metrics = $this->registry->getMetricFamilySamples();
        $ourMetric = collect($metrics)->first(fn($m) => $m->getName() === 'eventstream_requests');
        
        $this->assertNotNull($ourMetric);
        $samples = $ourMetric->getSamples();
        
        // Should have 3 different label combinations
        $this->assertCount(3, $samples);
        
        // Verify all values exist (order may vary)
        $values = collect($samples)->map(fn($s) => $s->getValue())->sort()->values()->all();
        $this->assertEquals([2, 5, 10], $values);
    }
}
