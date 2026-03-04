<?php

declare(strict_types=1);

namespace Nexus\DataExchangeOperations\DTOs;

use DateTimeImmutable;

final readonly class DataExchangeTaskStatus
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $taskId,
        public string $type,
        public string $status,
        public DateTimeImmutable $updatedAt,
        public array $payload = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'task_id' => $this->taskId,
            'type' => $this->type,
            'status' => $this->status,
            'updated_at' => $this->updatedAt->format(DateTimeImmutable::ATOM),
            'payload' => $this->payload,
        ];
    }
}
