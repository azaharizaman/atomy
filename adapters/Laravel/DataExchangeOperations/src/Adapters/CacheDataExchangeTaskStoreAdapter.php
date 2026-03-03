<?php

declare(strict_types=1);

namespace Nexus\Laravel\DataExchangeOperations\Adapters;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Nexus\DataExchangeOperations\Contracts\DataExchangeTaskStoreInterface;
use Nexus\DataExchangeOperations\DTOs\DataExchangeTaskStatus;

final readonly class CacheDataExchangeTaskStoreAdapter implements DataExchangeTaskStoreInterface
{
    private const KEY_PREFIX = 'data_exchange_task:';

    public function __construct(private CacheRepository $cache) {}

    public function save(DataExchangeTaskStatus $status): void
    {
        $this->cache->put(self::KEY_PREFIX . $status->taskId, $status->toArray(), now()->addDay());
    }

    public function find(string $taskId): ?DataExchangeTaskStatus
    {
        $cacheKey = self::KEY_PREFIX . $taskId;
        $raw = $this->cache->get($cacheKey);
        if (!is_array($raw)) {
            return null;
        }

        if (
            !isset($raw['task_id'], $raw['type'], $raw['status'], $raw['updated_at']) ||
            !is_string($raw['task_id']) ||
            !is_string($raw['type']) ||
            !is_string($raw['status']) ||
            !is_string($raw['updated_at']) ||
            !is_array($raw['payload'] ?? [])
        ) {
            $this->cache->forget($cacheKey);
            return null;
        }

        try {
            $updatedAt = new \DateTimeImmutable($raw['updated_at']);
        } catch (\Throwable) {
            $this->cache->forget($cacheKey);
            return null;
        }

        return new DataExchangeTaskStatus(
            taskId: $raw['task_id'],
            type: $raw['type'],
            status: $raw['status'],
            updatedAt: $updatedAt,
            payload: $raw['payload'],
        );
    }

    public function findForTenant(string $tenantId, string $taskId): ?DataExchangeTaskStatus
    {
        $status = $this->find($taskId);
        if ($status === null) {
            return null;
        }

        $statusTenant = $status->payload['tenant_id'] ?? null;
        if (!is_string($statusTenant) || $statusTenant !== $tenantId) {
            return null;
        }

        return $status;
    }
}
