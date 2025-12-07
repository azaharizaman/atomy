<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Context for workflow execution.
 */
final readonly class WorkflowContext
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $userId User initiating the workflow
     * @param array<string, mixed> $data Workflow-specific data
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $tenantId,
        public string $userId,
        public array $data = [],
        public array $metadata = [],
    ) {}

    /**
     * Get a specific data value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Check if a data key exists.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Create new context with additional data.
     *
     * @param array<string, mixed> $data Data to merge
     */
    public function withData(array $data): self
    {
        return new self(
            tenantId: $this->tenantId,
            userId: $this->userId,
            data: array_merge($this->data, $data),
            metadata: $this->metadata,
        );
    }

    /**
     * Create new context with additional metadata.
     *
     * @param array<string, mixed> $metadata Metadata to merge
     */
    public function withMetadata(array $metadata): self
    {
        return new self(
            tenantId: $this->tenantId,
            userId: $this->userId,
            data: $this->data,
            metadata: array_merge($this->metadata, $metadata),
        );
    }
}
