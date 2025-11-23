<?php

declare(strict_types=1);

namespace Nexus\Monitoring\Services;

use Nexus\Monitoring\Contracts\MetricRetentionInterface;
use Nexus\Monitoring\Contracts\MetricStorageInterface;
use Psr\Log\LoggerInterface;

/**
 * MetricRetentionService
 *
 * Manages metric retention policies and automated cleanup.
 * Prunes old metrics based on configurable retention periods.
 *
 * @package Nexus\Monitoring\Services
 */
final readonly class MetricRetentionService
{
    public function __construct(
        private MetricStorageInterface $storage,
        private MetricRetentionInterface $retentionPolicy,
        private LoggerInterface $logger
    ) {}

    /**
     * Prune metrics older than retention period.
     *
     * @param int|null $batchSize Maximum number of metrics to delete in one operation
     * @return int Number of metrics pruned
     */
    public function pruneExpiredMetrics(?int $batchSize = null): int
    {
        $cutoffTime = time() - $this->retentionPolicy->getRetentionPeriod();
        $prunedCount = 0;

        $this->logger->info('Starting metric retention cleanup', [
            'cutoff_timestamp' => $cutoffTime,
            'cutoff_date' => date('Y-m-d H:i:s', $cutoffTime),
            'batch_size' => $batchSize,
        ]);

        try {
            $prunedCount = $this->storage->deleteMetricsOlderThan($cutoffTime, $batchSize);

            $this->logger->info('Metric retention cleanup completed', [
                'pruned_count' => $prunedCount,
                'cutoff_timestamp' => $cutoffTime,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Metric retention cleanup failed', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            throw $e;
        }

        return $prunedCount;
    }

    /**
     * Prune metrics for a specific metric key.
     *
     * @param string $metricKey
     * @return int Number of metrics pruned
     */
    public function pruneMetric(string $metricKey): int
    {
        $cutoffTime = time() - $this->retentionPolicy->getRetentionPeriod();

        $this->logger->info('Pruning specific metric', [
            'metric_key' => $metricKey,
            'cutoff_timestamp' => $cutoffTime,
        ]);

        try {
            $prunedCount = $this->storage->deleteMetric($metricKey, $cutoffTime);

            $this->logger->info('Metric pruned successfully', [
                'metric_key' => $metricKey,
                'pruned_count' => $prunedCount,
            ]);

            return $prunedCount;
        } catch (\Throwable $e) {
            $this->logger->error('Metric pruning failed', [
                'metric_key' => $metricKey,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get retention statistics.
     *
     * @return array<string, mixed>
     */
    public function getRetentionStats(): array
    {
        $retentionPeriod = $this->retentionPolicy->getRetentionPeriod();
        $cutoffTime = time() - $retentionPeriod;

        return [
            'retention_period_seconds' => $retentionPeriod,
            'retention_period_days' => round($retentionPeriod / 86400, 2),
            'cutoff_timestamp' => $cutoffTime,
            'cutoff_date' => date('Y-m-d H:i:s', $cutoffTime),
            'metrics_eligible_for_cleanup' => $this->storage->countMetricsOlderThan($cutoffTime),
        ];
    }

    /**
     * Check if automatic cleanup is needed.
     *
     * @param int $threshold Minimum number of expired metrics to trigger cleanup
     * @return bool
     */
    public function needsCleanup(int $threshold = 1000): bool
    {
        $cutoffTime = time() - $this->retentionPolicy->getRetentionPeriod();
        $expiredCount = $this->storage->countMetricsOlderThan($cutoffTime);

        return $expiredCount >= $threshold;
    }

    /**
     * Get the retention policy instance.
     *
     * @return MetricRetentionInterface
     */
    public function getRetentionPolicy(): MetricRetentionInterface
    {
        return $this->retentionPolicy;
    }
}
