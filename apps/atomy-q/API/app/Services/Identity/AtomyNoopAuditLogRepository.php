<?php

declare(strict_types=1);

namespace App\Services\Identity;

use Nexus\AuditLogger\Contracts\AuditLogInterface;
use Nexus\AuditLogger\Contracts\AuditLogRepositoryInterface;

/**
 * Drops audit writes on the floor until a persisted audit pipeline is adopted for Atomy-Q.
 */
final class AtomyNoopAuditLogRepository implements AuditLogRepositoryInterface
{
    public function create(array $data): AuditLogInterface
    {
        return new AtomyNoopAuditLogEntry($data);
    }

    public function findById($id): ?AuditLogInterface
    {
        return null;
    }

    public function search(
        array $filters = [],
        int $page = 1,
        int $perPage = 50,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
    ): array {
        return ['data' => [], 'total' => 0];
    }

    public function getBySubject(string $subjectType, $subjectId, int $limit = 100): array
    {
        return [];
    }

    public function getByCauser(string $causerType, $causerId, int $limit = 100): array
    {
        return [];
    }

    public function getByBatchUuid(string $batchUuid): array
    {
        return [];
    }

    public function getByLevel(int $level, int $limit = 100): array
    {
        return [];
    }

    public function getByTenant($tenantId, int $limit = 100): array
    {
        return [];
    }

    public function getExpired(?\DateTimeInterface $beforeDate = null, int $limit = 1000): array
    {
        return [];
    }

    public function deleteExpired(?\DateTimeInterface $beforeDate = null): int
    {
        return 0;
    }

    public function deleteByIds(array $ids): int
    {
        return 0;
    }

    public function getStatistics(array $filters = []): array
    {
        return [
            'total_count' => 0,
            'by_log_name' => [],
            'by_level' => [],
            'by_event' => [],
            'by_date' => [],
        ];
    }

    public function exportToArray(array $filters = [], int $limit = 10000): array
    {
        return [];
    }
}

/**
 * @internal
 */
final readonly class AtomyNoopAuditLogEntry implements AuditLogInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(private array $data)
    {
    }

    public function getId(): string
    {
        return 'noop';
    }

    public function getLogName(): string
    {
        return (string) ($this->data['log_name'] ?? 'noop');
    }

    public function getDescription(): string
    {
        return (string) ($this->data['description'] ?? '');
    }

    public function getSubjectType(): ?string
    {
        $v = $this->data['subject_type'] ?? null;

        return is_string($v) ? $v : null;
    }

    public function getSubjectId(): ?string
    {
        $v = $this->data['subject_id'] ?? null;
        if ($v === null) {
            return null;
        }

        return is_scalar($v) ? (string) $v : null;
    }

    public function getCauserType(): ?string
    {
        $v = $this->data['causer_type'] ?? null;

        return is_string($v) ? $v : null;
    }

    public function getCauserId(): ?string
    {
        $v = $this->data['causer_id'] ?? null;
        if ($v === null) {
            return null;
        }

        return is_scalar($v) ? (string) $v : null;
    }

    public function getProperties(): array
    {
        $p = $this->data['properties'] ?? $this->data['changes'] ?? [];

        return is_array($p) ? $p : [];
    }

    public function getEvent(): ?string
    {
        $v = $this->data['event'] ?? null;

        return is_string($v) ? $v : null;
    }

    public function getLevel(): int
    {
        return 1;
    }

    public function getBatchUuid(): ?string
    {
        return null;
    }

    public function getIpAddress(): ?string
    {
        return null;
    }

    public function getUserAgent(): ?string
    {
        return null;
    }

    public function getTenantId(): ?string
    {
        return null;
    }

    public function getRetentionDays(): int
    {
        return 90;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        $c = $this->data['created_at'] ?? null;
        if ($c instanceof \DateTimeInterface) {
            return $c instanceof \DateTimeImmutable ? $c : \DateTimeImmutable::createFromInterface($c);
        }

        return new \DateTimeImmutable();
    }

    public function getExpiresAt(): \DateTimeInterface
    {
        return (new \DateTimeImmutable())->modify('+90 days');
    }

    public function isExpired(): bool
    {
        return false;
    }
}
