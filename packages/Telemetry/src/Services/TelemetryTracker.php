<?php

declare(strict_types=1);

namespace Nexus\Telemetry\Services;

use DateTimeImmutable;
use Nexus\Telemetry\Contracts\CardinalityGuardInterface;
use Nexus\Telemetry\Contracts\MetricStorageInterface;
use Nexus\Telemetry\Contracts\SamplingStrategyInterface;
use Nexus\Telemetry\Contracts\TelemetryTrackerInterface;
use Nexus\Telemetry\Exceptions\CardinalityLimitExceededException;
use Nexus\Telemetry\ValueObjects\Metric;
use Nexus\Telemetry\ValueObjects\MetricType;
use Psr\Log\LoggerInterface;

/**
 * Primary service for recording application telemetry metrics.
 * 
 * Features:
 * - Multi-tenancy auto-tagging (if TenantContextInterface provided)
 * - Cardinality protection via CardinalityGuardInterface
 * - Optional sampling strategy for high-volume metrics
 * - OpenTelemetry trace context propagation
 * - Structured logging of all metric operations
 * 
 * @see TelemetryTrackerInterface
 */
final readonly class TelemetryTracker implements TelemetryTrackerInterface
{
    /**
     * @param object|null $tenantContext Optional tenant context (duck-typed, expects getCurrentTenantId() method)
     */
    public function __construct(
        private MetricStorageInterface $storage,
        private CardinalityGuardInterface $cardinalityGuard,
        private LoggerInterface $logger,
        private ?object $tenantContext = null,
        private ?SamplingStrategyInterface $samplingStrategy = null
    ) {}
    
    public function gauge(
        string $key,
        float $value,
        array $tags = [],
        ?string $traceId = null,
        ?string $spanId = null
    ): void {
        $this->recordMetric(MetricType::GAUGE, $key, $value, $tags, $traceId, $spanId);
    }
    
    public function increment(
        string $key,
        float $value = 1.0,
        array $tags = [],
        ?string $traceId = null,
        ?string $spanId = null
    ): void {
        $this->recordMetric(MetricType::COUNTER, $key, $value, $tags, $traceId, $spanId);
    }
    
    public function timing(
        string $key,
        float $milliseconds,
        array $tags = [],
        ?string $traceId = null,
        ?string $spanId = null
    ): void {
        $this->recordMetric(MetricType::TIMING, $key, $milliseconds, $tags, $traceId, $spanId);
    }
    
    public function histogram(
        string $key,
        float $value,
        array $tags = [],
        ?string $traceId = null,
        ?string $spanId = null
    ): void {
        $this->recordMetric(MetricType::HISTOGRAM, $key, $value, $tags, $traceId, $spanId);
    }
    
    /**
     * Internal method to record a metric with all validations and enrichments.
     *
     * @param array<string, scalar> $tags
     * @throws CardinalityLimitExceededException
     */
    private function recordMetric(
        MetricType $type,
        string $key,
        float $value,
        array $tags,
        ?string $traceId,
        ?string $spanId
    ): void {
        // Auto-append tenant_id tag if tenant context exists and not already set
        if ($this->tenantContext !== null && !isset($tags['tenant_id'])) {
            $tenantId = $this->tenantContext->getCurrentTenantId();
            if ($tenantId !== null) {
                $tags['tenant_id'] = $tenantId;
            }
        }
        
        // Create metric VO
        $metric = new Metric(
            name: $key,
            type: $type,
            value: $value,
            tags: $tags,
            timestamp: new DateTimeImmutable(),
            traceId: $traceId,
            spanId: $spanId
        );
        
        // Apply sampling strategy if provided
        if ($this->samplingStrategy !== null && !$this->samplingStrategy->shouldSample($metric)) {
            $this->logger->debug('Metric sampled out (not recorded)', [
                'metric_name' => $key,
                'metric_type' => $type->value,
            ]);
            return;
        }
        
        // Validate cardinality before storage
        try {
            $this->cardinalityGuard->validateTags($tags);
        } catch (CardinalityLimitExceededException $e) {
            $this->logger->error('Cardinality limit exceeded for metric', [
                'metric_name' => $key,
                'tag_key' => $e->tagKey,
                'current_cardinality' => $e->currentCardinality,
                'limit' => $e->limit,
            ]);
            throw $e;
        }
        
        // Store the metric
        $this->storage->store($metric);
        
        // Log successful recording
        $this->logger->debug('Metric recorded successfully', [
            'metric_name' => $key,
            'metric_type' => $type->value,
            'value' => $value,
            'tags' => $tags,
            'has_trace_context' => $metric->hasTraceContext(),
        ]);
    }
}
