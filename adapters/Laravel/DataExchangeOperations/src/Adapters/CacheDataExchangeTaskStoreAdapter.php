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
        $raw = $this->cache->get(self::KEY_PREFIX . $taskId);
        if (!is_array($raw)) {
            return null;
        }

        return new DataExchangeTaskStatus(
            taskId: (string) $raw['task_id'],
            type: (string) $raw['type'],
            status: (string) $raw['status'],
            updatedAt: new \DateTimeImmutable((string) $raw['updated_at']),
            payload: is_array($raw['payload'] ?? null) ? $raw['payload'] : [],
        );
    }
}
