<?php

declare(strict_types=1);

namespace Nexus\Analytics\ValueObjects;

/**
 * Immutable value object representing a query execution result
 */
final readonly class QueryResult implements \Nexus\Analytics\Contracts\QueryResultInterface
{
    /**
     * @param string $queryId
     * @param array<string, mixed> $data
     * @param array<string, mixed> $metadata
     * @param bool $isSuccessful
     * @param string|null $error
     * @param \DateTimeImmutable $executedAt
     * @param int $durationMs
     */
    public function __construct(
        private string $queryId,
        private array $data,
        private array $metadata = [],
        private bool $isSuccessful = true,
        private ?string $error = null,
        private ?\DateTimeImmutable $executedAt = null,
        private int $durationMs = 0
    ) {
    }

    public function getQueryId(): string
    {
        return $this->queryId;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function isSuccessful(): bool
    {
        return $this->isSuccessful;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getExecutedAt(): \DateTimeImmutable
    {
        return $this->executedAt ?? new \DateTimeImmutable();
    }

    public function getDurationMs(): int
    {
        return $this->durationMs;
    }

    /**
     * Create a successful result
     *
     * @param string $queryId
     * @param array<string, mixed> $data
     * @param int $durationMs
     * @return self
     */
    public static function success(string $queryId, array $data, int $durationMs = 0): self
    {
        return new self(
            queryId: $queryId,
            data: $data,
            metadata: [],
            isSuccessful: true,
            error: null,
            executedAt: new \DateTimeImmutable(),
            durationMs: $durationMs
        );
    }

    /**
     * Create a failed result
     *
     * @param string $queryId
     * @param string $error
     * @param int $durationMs
     * @return self
     */
    public static function failure(string $queryId, string $error, int $durationMs = 0): self
    {
        return new self(
            queryId: $queryId,
            data: [],
            metadata: [],
            isSuccessful: false,
            error: $error,
            executedAt: new \DateTimeImmutable(),
            durationMs: $durationMs
        );
    }

    /**
     * Convert to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'query_id' => $this->queryId,
            'data' => $this->data,
            'metadata' => $this->metadata,
            'is_successful' => $this->isSuccessful,
            'error' => $this->error,
            'executed_at' => $this->getExecutedAt()->format('Y-m-d H:i:s'),
            'duration_ms' => $this->durationMs,
        ];
    }
}
