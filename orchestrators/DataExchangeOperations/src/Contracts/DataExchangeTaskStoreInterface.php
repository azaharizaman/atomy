<?php

declare(strict_types=1);

namespace Nexus\DataExchangeOperations\Contracts;

use Nexus\DataExchangeOperations\DTOs\DataExchangeTaskStatus;

interface DataExchangeTaskStoreInterface
{
    public function save(DataExchangeTaskStatus $status): void;

    public function find(string $taskId): ?DataExchangeTaskStatus;
}
