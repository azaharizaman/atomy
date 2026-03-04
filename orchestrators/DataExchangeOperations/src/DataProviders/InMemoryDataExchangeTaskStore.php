<?php

declare(strict_types=1);

namespace Nexus\DataExchangeOperations\DataProviders;

use Nexus\DataExchangeOperations\Contracts\DataExchangeTaskStoreInterface;
use Nexus\DataExchangeOperations\DTOs\DataExchangeTaskStatus;

final class InMemoryDataExchangeTaskStore implements DataExchangeTaskStoreInterface
{
    /** @var array<string, DataExchangeTaskStatus> */
    private array $items = [];

    public function save(DataExchangeTaskStatus $status): void
    {
        $this->items[$status->taskId] = $status;
    }

    public function find(string $taskId): ?DataExchangeTaskStatus
    {
        return $this->items[$taskId] ?? null;
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
