<?php

declare(strict_types=1);

namespace Nexus\Monitoring\Contracts;

use DateTimeInterface;
use Nexus\Monitoring\ValueObjects\AggregationSpec;
use Nexus\Monitoring\ValueObjects\Metric;
use Nexus\Monitoring\ValueObjects\QuerySpec;

/**
 * Metric Storage Interface
 *
 * Contract for persisting and querying metrics from time-series storage.
 * Implementation is TSDB-agnostic (supports Prometheus, InfluxDB, PostgreSQL, etc.).
 *
 * @package Nexus\Monitoring\Contracts
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
     * @throws \Nexus\Monitoring\Exceptions\UnsupportedAggregationException If function not supported
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
}
