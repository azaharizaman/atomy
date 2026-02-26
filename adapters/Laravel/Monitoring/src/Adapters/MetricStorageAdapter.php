<?php

declare(strict_types=1);

namespace Nexus\Laravel\Monitoring\Adapters;

use DateTimeInterface;
use Nexus\Telemetry\Contracts\MetricStorageInterface;
use Nexus\Telemetry\ValueObjects\AggregationSpec;
use Nexus\Telemetry\ValueObjects\Metric;
use Nexus\Telemetry\ValueObjects\QuerySpec;
use Psr\Log\LoggerInterface;

/**
 * Laravel implementation of MetricStorageInterface.
 */
class MetricStorageAdapter implements MetricStorageInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    /**
     * {@inheritdoc}
     */
    public function store(Metric $metric): void
    {
        $this->logger->debug('Storing metric', [
            'key' => $metric->getKey(),
            'value' => $metric->getValue(),
        ]);
        
        // Implementation would use database
        throw new \RuntimeException('MetricStorageAdapter::store() not implemented - requires Metric model');
    }

    /**
     * {@inheritdoc}
     */
    public function query(QuerySpec $spec): array
    {
        $this->logger->debug('Querying metrics', [
            'key' => $spec->getKey(),
            'from' => $spec->getFrom(),
            'to' => $spec->getTo(),
        ]);
        
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function aggregate(AggregationSpec $spec): float
    {
        $this->logger->debug('Aggregating metrics', [
            'key' => $spec->getKey(),
            'function' => $spec->getFunction(),
        ]);
        
        return 0.0;
    }

    /**
     * {@inheritdoc}
     */
    public function purgeMetricsBefore(DateTimeInterface $before): int
    {
        $this->logger->info('Purging metrics before', ['before' => $before->format(\DateTimeInterface::ATOM)]);
        
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMetricsOlderThan(int $cutoffTimestamp, ?int $batchSize = null): int
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMetric(string $metricKey, int $cutoffTimestamp): int
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function countMetricsOlderThan(int $cutoffTimestamp): int
    {
        return 0;
    }
}
