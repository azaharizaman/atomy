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
}
