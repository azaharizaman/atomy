<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\IntegrationLog as IntegrationLogModel;
use Nexus\Connector\Contracts\IntegrationLoggerInterface;
use Nexus\Connector\ValueObjects\{HttpMethod, IntegrationLog, IntegrationStatus};

/**
 * Database implementation of integration logger.
 */
final readonly class DbIntegrationLogger implements IntegrationLoggerInterface
{
    /**
     * Log an integration attempt.
     */
    public function log(IntegrationLog $log): void
    {
        IntegrationLogModel::create([
            'id' => $log->id,
            'tenant_id' => $log->tenantId,
            'service_name' => $log->serviceName,
            'endpoint' => $log->endpoint,
            'method' => $log->method->value,
            'status' => $log->status->value,
            'http_status_code' => $log->httpStatusCode,
            'duration_ms' => $log->durationMs,
            'request_data' => $log->requestData,
            'response_data' => $log->responseData,
            'error_message' => $log->errorMessage,
            'attempt_number' => $log->attemptNumber,
            'created_at' => $log->timestamp,
        ]);
    }

    /**
     * Retrieve integration logs with filtering.
     */
    public function getLogs(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $query = IntegrationLogModel::query();

        // Apply filters
        if (isset($filters['service'])) {
            $query->forService($filters['service']);
        }

        if (isset($filters['status'])) {
            $query->withStatus($filters['status']);
        }

        if (isset($filters['tenant_id'])) {
            $query->forTenant($filters['tenant_id']);
        }

        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $query->betweenDates(
                new \DateTimeImmutable($filters['date_from']),
                new \DateTimeImmutable($filters['date_to'])
            );
        }

        // Order and paginate
        $models = $query->orderByDesc('created_at')
            ->limit($limit)
            ->offset($offset)
            ->get();

        // Convert to value objects
        return $models->map(fn($model) => $this->toValueObject($model))->all();
    }

    /**
     * Get integration metrics for a service.
     */
    public function getMetrics(string $serviceName, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $logs = IntegrationLogModel::forService($serviceName)
            ->betweenDates($from, $to)
            ->get();

        $successCount = $logs->where('status', 'success')->count();
        $failureCount = $logs->where('status', '!=', 'success')->count();
        $totalCount = $logs->count();

        $successRate = $totalCount > 0 ? ($successCount / $totalCount) * 100 : 0;
        $avgDuration = $logs->avg('duration_ms') ?? 0;

        return [
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'success_rate' => round($successRate, 2),
            'avg_duration_ms' => round($avgDuration, 2),
        ];
    }

    /**
     * Purge old integration logs based on retention policy.
     */
    public function purgeOldLogs(int $retentionDays): int
    {
        $cutoffDate = now()->subDays($retentionDays);

        return IntegrationLogModel::where('created_at', '<', $cutoffDate)->delete();
    }

    /**
     * Convert Eloquent model to value object.
     */
    private function toValueObject(IntegrationLogModel $model): IntegrationLog
    {
        return new IntegrationLog(
            id: $model->id,
            serviceName: $model->service_name,
            endpoint: $model->endpoint,
            method: HttpMethod::from($model->method),
            status: IntegrationStatus::from($model->status),
            httpStatusCode: $model->http_status_code,
            durationMs: $model->duration_ms,
            requestData: $model->request_data ?? [],
            responseData: $model->response_data,
            errorMessage: $model->error_message,
            timestamp: $model->created_at->toDateTimeImmutable(),
            tenantId: $model->tenant_id,
            attemptNumber: $model->attempt_number
        );
    }
}
