<?php

declare(strict_types=1);

namespace Nexus\Telemetry\Contracts;

use DateTimeInterface;
use Nexus\Telemetry\ValueObjects\AggregationSpec;
use Nexus\Telemetry\ValueObjects\Metric;
use Nexus\Telemetry\ValueObjects\QuerySpec;

/**
 * Metric Storage Interface
 *
 * Contract for persisting and querying metrics from time-series storage.
 * Implementation is TSDB-agnostic (supports Prometheus, InfluxDB, PostgreSQL, etc.).
 *
 * @package Nexus\Telemetry\Contracts
 */
interface MetricStorageInterface
{
    /**
     * Store a metric in persistent storage.
     *
     * @param Metric $metric Metric to store
     * @return void
     */
    public function store(Metric $metric): void;

    /**
     * Query metrics by specification.
     *
     * @param QuerySpec $spec Query specification
     * @return array<Metric> Array of matching metrics
     */
    public function query(QuerySpec $spec): array;

    /**
     * Aggregate metrics using specified function.
     *
     * @param AggregationSpec $spec Aggregation specification
     * @return float Aggregated result
     * @throws \Nexus\Telemetry\Exceptions\UnsupportedAggregationException If function not supported
     */
    public function aggregate(AggregationSpec $spec): float;

    /**
     * Purge metrics recorded before specified date.
     * Used by retention policy service.
     *
     * @param DateTimeInterface $before Cutoff date (exclusive)
     * @return int Number of metrics purged
     */
    public function purgeMetricsBefore(DateTimeInterface $before): int;

    /**
     * Delete metrics older than specified timestamp.
     *
     * @param int $cutoffTimestamp Unix timestamp cutoff (exclusive)
     * @param int|null $batchSize Maximum number of metrics to delete
     * @return int Number of metrics deleted
     */
    public function deleteMetricsOlderThan(int $cutoffTimestamp, ?int $batchSize = null): int;

    /**
     * Delete metrics for specific key older than timestamp.
     *
     * @param string $metricKey Metric key to delete
     * @param int $cutoffTimestamp Unix timestamp cutoff (exclusive)
     * @return int Number of metrics deleted
     */
    public function deleteMetric(string $metricKey, int $cutoffTimestamp): int;

    /**
     * Count metrics older than specified timestamp.
     *
     * @param int $cutoffTimestamp Unix timestamp cutoff (exclusive)
     * @return int Number of metrics
     */
    public function countMetricsOlderThan(int $cutoffTimestamp): int;
}
