<?php

declare(strict_types=1);

namespace Nexus\Connector\Contracts;

use Nexus\Connector\ValueObjects\IntegrationLog;

/**
 * Interface for logging all external integration attempts.
 *
 * Provides audit trail for compliance and debugging purposes.
 */
interface IntegrationLoggerInterface
{
    /**
     * Log an integration attempt.
     *
     * @param IntegrationLog $log The integration log entry
     * @return void
     */
    public function log(IntegrationLog $log): void;

    /**
     * Retrieve integration logs with filtering.
     *
     * @param array<string, mixed> $filters Key-value pairs for filtering (service, status, date_from, date_to, etc.)
     * @param int $limit Maximum number of records to return
     * @param int $offset Offset for pagination
     * @return array<int, IntegrationLog> Array of integration log entries
     */
    public function getLogs(array $filters = [], int $limit = 100, int $offset = 0): array;

    /**
     * Get integration metrics for a service.
     *
     * @param string $serviceName The name of the external service
     * @param \DateTimeInterface $from Start date for metrics
     * @param \DateTimeInterface $to End date for metrics
     * @return array{success_count: int, failure_count: int, success_rate: float, avg_duration_ms: float}
     */
    public function getMetrics(string $serviceName, \DateTimeInterface $from, \DateTimeInterface $to): array;

    /**
     * Purge old integration logs based on retention policy.
     *
     * @param int $retentionDays Number of days to retain logs
     * @return int Number of logs deleted
     */
    public function purgeOldLogs(int $retentionDays): int;
}
