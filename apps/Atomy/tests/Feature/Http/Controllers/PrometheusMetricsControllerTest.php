<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Services\Monitoring\PrometheusTelemetryAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;
use Tests\TestCase;

/**
 * Tests for PrometheusMetricsController
 *
 * Verifies the HTTP endpoint correctly exposes metrics in Prometheus format.
 */
class PrometheusMetricsControllerTest extends TestCase
{
    use RefreshDatabase;

    private PrometheusTelemetryAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create adapter with InMemory storage
        $registry = new CollectorRegistry(new InMemory());
        $this->adapter = new PrometheusTelemetryAdapter($registry);
        
        // Bind to container
        $this->app->instance(PrometheusTelemetryAdapter::class, $this->adapter);
    }

    /** @test */
    public function it_exposes_prometheus_metrics_endpoint(): void
    {
        // Arrange - Record some metrics
        $this->adapter->increment('test_counter', 42, ['label' => 'value']);
        $this->adapter->gauge('test_gauge', 100);

        // Act
        $response = $this->get('/api/metrics/prometheus');

        // Assert
        $response->assertStatus(200);
    }

    /** @test */
    public function it_returns_correct_content_type_header(): void
    {
        // Act
        $response = $this->get('/api/metrics/prometheus');

        // Assert
        $response->assertHeader('Content-Type', 'text/plain; version=0.0.4; charset=utf-8');
    }

    /** @test */
    public function it_returns_metrics_in_prometheus_text_format(): void
    {
        // Arrange
        $this->adapter->increment('events_appended_total', 150, ['stream_name' => 'account-1000']);
        $this->adapter->gauge('projection_lag_seconds', 12.5, ['projector' => 'balance']);
        $this->adapter->timing('event_append_duration_ms', 35.2, []);

        // Act
        $response = $this->get('/api/metrics/prometheus');

        // Assert
        $content = $response->getContent();
        
        // Verify Prometheus format structure
        $this->assertStringContainsString('# HELP eventstream_events_appended_total', $content);
        $this->assertStringContainsString('# TYPE eventstream_events_appended_total counter', $content);
        $this->assertStringContainsString('eventstream_events_appended_total{stream_name="account-1000"} 150', $content);
        
        $this->assertStringContainsString('# HELP eventstream_projection_lag_seconds', $content);
        $this->assertStringContainsString('# TYPE eventstream_projection_lag_seconds gauge', $content);
        
        $this->assertStringContainsString('# HELP eventstream_event_append_duration_ms', $content);
        $this->assertStringContainsString('# TYPE eventstream_event_append_duration_ms histogram', $content);
    }

    /** @test */
    public function it_does_not_require_authentication(): void
    {
        // Prometheus scraper needs unauthenticated access
        
        // Act - No auth headers
        $response = $this->get('/api/metrics/prometheus');

        // Assert
        $response->assertStatus(200);
    }

    /** @test */
    public function it_returns_empty_metrics_when_no_data_recorded(): void
    {
        // Act - Fresh adapter with no metrics
        $response = $this->get('/api/metrics/prometheus');

        // Assert
        $response->assertStatus(200);
        
        // Content should be minimal (just comments or empty)
        $content = $response->getContent();
        $this->assertIsString($content);
    }

    /** @test */
    public function it_handles_special_characters_in_label_values(): void
    {
        // Arrange
        $this->adapter->increment('test_metric', 1, [
            'label_with_quotes' => 'value "with" quotes',
            'label_with_newline' => "value\nwith\nnewlines",
        ]);

        // Act
        $response = $this->get('/api/metrics/prometheus');

        // Assert
        $response->assertStatus(200);
        
        // Prometheus should escape special characters
        $content = $response->getContent();
        $this->assertStringContainsString('eventstream_test_metric', $content);
    }

    /** @test */
    public function it_exports_all_metric_types_correctly(): void
    {
        // Arrange - Create one of each metric type
        $this->adapter->increment('counter_metric', 10);
        $this->adapter->gauge('gauge_metric', 50);
        $this->adapter->timing('timing_metric', 100);
        $this->adapter->histogram('histogram_metric', 200);

        // Act
        $response = $this->get('/api/metrics/prometheus');

        // Assert
        $content = $response->getContent();
        
        $this->assertStringContainsString('# TYPE eventstream_counter_metric counter', $content);
        $this->assertStringContainsString('# TYPE eventstream_gauge_metric gauge', $content);
        $this->assertStringContainsString('# TYPE eventstream_timing_metric histogram', $content);
        $this->assertStringContainsString('# TYPE eventstream_histogram_metric histogram', $content);
    }

    /** @test */
    public function it_returns_consistent_metrics_across_multiple_requests(): void
    {
        // Arrange
        $this->adapter->increment('persistent_counter', 100);

        // Act
        $response1 = $this->get('/api/metrics/prometheus');
        $response2 = $this->get('/api/metrics/prometheus');

        // Assert - Both responses should show the same counter value
        $this->assertEquals($response1->getContent(), $response2->getContent());
        $this->assertStringContainsString('eventstream_persistent_counter 100', $response1->getContent());
    }

    /** @test */
    public function it_handles_high_cardinality_metrics(): void
    {
        // Arrange - Create many label combinations
        for ($i = 0; $i < 100; $i++) {
            $this->adapter->increment('high_cardinality_metric', 1, [
                'dimension_1' => "value_{$i}",
                'dimension_2' => "category_" . ($i % 10),
            ]);
        }

        // Act
        $response = $this->get('/api/metrics/prometheus');

        // Assert
        $response->assertStatus(200);
        
        $content = $response->getContent();
        $this->assertStringContainsString('eventstream_high_cardinality_metric', $content);
        
        // Verify response is not too large (basic sanity check)
        $this->assertLessThan(1024 * 1024, strlen($content)); // < 1MB
    }

    /** @test */
    public function it_supports_concurrent_metric_recording(): void
    {
        // Arrange - Simulate concurrent requests recording metrics
        $this->adapter->increment('concurrent_metric', 1, ['source' => 'request_1']);
        $this->adapter->increment('concurrent_metric', 1, ['source' => 'request_2']);
        $this->adapter->increment('concurrent_metric', 1, ['source' => 'request_3']);

        // Act
        $response = $this->get('/api/metrics/prometheus');

        // Assert
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Should have 3 separate time series
        $this->assertStringContainsString('source="request_1"', $content);
        $this->assertStringContainsString('source="request_2"', $content);
        $this->assertStringContainsString('source="request_3"', $content);
    }
}
