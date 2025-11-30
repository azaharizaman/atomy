<?php

declare(strict_types=1);

namespace Nexus\Import\ValueObjects;

/**
 * Import record value object
 * 
 * Represents a persisted import job/record in the system
 */
readonly class ImportRecord
{
    public function __construct(
        public string $id,
        public string $handlerType,
        public ImportMode $mode,
        public ImportStatus $status,
        public ImportMetadata $metadata,
        public ?\DateTimeImmutable $startedAt = null,
        public ?\DateTimeImmutable $completedAt = null
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'handler_type' => $this->handlerType,
            'mode' => $this->mode->value,
            'status' => $this->status->value,
            'metadata' => $this->metadata->toArray(),
            'started_at' => $this->startedAt?->format('c'),
            'completed_at' => $this->completedAt?->format('c'),
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            handlerType: $data['handlerType'] ?? $data['handler_type'],
            mode: $data['mode'] instanceof ImportMode ? $data['mode'] : ImportMode::from($data['mode']),
            status: $data['status'] instanceof ImportStatus ? $data['status'] : ImportStatus::from($data['status']),
            metadata: $data['metadata'],
            startedAt: $data['startedAt'] ?? $data['started_at'] ?? null,
            completedAt: $data['completedAt'] ?? $data['completed_at'] ?? null
        );
    }
}
