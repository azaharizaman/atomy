<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Models\AuditLog as AuditLogModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Nexus\AuditLogger\Contracts\AuditLogInterface;
use Nexus\AuditLogger\Contracts\AuditLogRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Atomy-Q audit log persistence adapter.
 *
 * Note: the monorepo includes a Laravel AuditLogger adapter package, but the Atomy-Q API
 * composer setup may not autoload it yet. This keeps Gap 7 audit persistence unblocked.
 */
final readonly class AtomyAuditLogRepository implements AuditLogRepositoryInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function create(array $data): AuditLogInterface
    {
        $event = $this->normalizeString($data['event'] ?? null);
        $logName = $this->normalizeString($data['log_name'] ?? null) ?? 'identity';
        $description = $this->normalizeString($data['description'] ?? null) ?? ($event ?? 'audit');

        $retentionDays = $this->normalizeInt($data['retention_days'] ?? null) ?? 90;
        if ($retentionDays < 1) {
            $retentionDays = 1;
        }

        $createdAt = $this->normalizeDateTime($data['created_at'] ?? null) ?? new \DateTimeImmutable();
        $expiresAt = $this->normalizeDateTime($data['expires_at'] ?? null)
            ?? ($createdAt instanceof \DateTimeImmutable
                ? $createdAt->modify(sprintf('+%d days', $retentionDays))
                : \DateTimeImmutable::createFromInterface($createdAt)->modify(sprintf('+%d days', $retentionDays)));

        $properties = $data['properties'] ?? $data['changes'] ?? [];
        if (! is_array($properties)) {
            $properties = [];
        }

        $model = AuditLogModel::query()->create([
            'id' => $this->normalizeString($data['id'] ?? null) ?? (string) Str::ulid(),
            'log_name' => $logName,
            'description' => $description,
            'subject_type' => $this->normalizeString($data['subject_type'] ?? null),
            'subject_id' => $this->normalizeScalar($data['subject_id'] ?? null),
            'causer_type' => $this->normalizeString($data['causer_type'] ?? null),
            'causer_id' => $this->normalizeScalar($data['causer_id'] ?? null),
            'properties' => $properties,
            'event' => $event,
            'level' => $this->normalizeInt($data['level'] ?? null) ?? 1,
            'batch_uuid' => $this->normalizeString($data['batch_uuid'] ?? null),
            'ip_address' => $this->normalizeString($data['ip_address'] ?? null),
            'user_agent' => $this->normalizeString($data['user_agent'] ?? null),
            'tenant_id' => $this->normalizeScalar($data['tenant_id'] ?? null),
            'retention_days' => $retentionDays,
            'created_at' => $createdAt,
            'expires_at' => $expiresAt,
        ]);

        $this->logger->info('Created audit log', [
            'id' => $model->getId(),
            'event' => $model->getEvent(),
            'log_name' => $model->getLogName(),
        ]);

        return $model;
    }

    public function findById($id): ?AuditLogInterface
    {
        $key = $this->normalizeScalar($id);
        if ($key === null) {
            return null;
        }

        /** @var AuditLogModel|null $log */
        $log = AuditLogModel::query()->whereKey($key)->first();

        return $log;
    }

    public function search(
        array $filters = [],
        int $page = 1,
        int $perPage = 50,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
    ): array {
        $page = $page < 1 ? 1 : $page;
        $perPage = $perPage < 1 ? 1 : $perPage;

        $builder = AuditLogModel::query();
        $this->applyFilters($builder, $filters);

        $total = (clone $builder)->count();

        $sortDirection = strtolower(trim($sortDirection)) === 'asc' ? 'asc' : 'desc';
        $sortBy = in_array($sortBy, ['created_at', 'event', 'level', 'log_name'], true) ? $sortBy : 'created_at';

        /** @var list<AuditLogModel> $rows */
        $rows = $builder
            ->orderBy($sortBy, $sortDirection)
            ->forPage($page, $perPage)
            ->get()
            ->all();

        return ['data' => $rows, 'total' => $total];
    }

    public function getBySubject(string $subjectType, $subjectId, int $limit = 100): array
    {
        $limit = $limit < 1 ? 1 : $limit;

        return AuditLogModel::query()
            ->where('subject_type', $subjectType)
            ->where('subject_id', $this->normalizeScalar($subjectId))
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function getByCauser(string $causerType, $causerId, int $limit = 100): array
    {
        $limit = $limit < 1 ? 1 : $limit;

        return AuditLogModel::query()
            ->where('causer_type', $causerType)
            ->where('causer_id', $this->normalizeScalar($causerId))
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function getByBatchUuid(string $batchUuid): array
    {
        $uuid = $this->normalizeString($batchUuid);
        if ($uuid === null) {
            return [];
        }

        return AuditLogModel::query()
            ->where('batch_uuid', $uuid)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function getByLevel(int $level, int $limit = 100): array
    {
        $limit = $limit < 1 ? 1 : $limit;

        return AuditLogModel::query()
            ->where('level', $level)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function getByTenant($tenantId, int $limit = 100): array
    {
        $limit = $limit < 1 ? 1 : $limit;
        $normalized = $this->normalizeScalar($tenantId);
        if ($normalized === null) {
            return [];
        }

        return AuditLogModel::query()
            ->where('tenant_id', $normalized)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function getExpired(?\DateTimeInterface $beforeDate = null, int $limit = 1000): array
    {
        $limit = $limit < 1 ? 1 : $limit;
        $before = $beforeDate ?? new \DateTimeImmutable();

        return AuditLogModel::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $before)
            ->orderBy('expires_at', 'asc')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function deleteExpired(?\DateTimeInterface $beforeDate = null): int
    {
        $before = $beforeDate ?? new \DateTimeImmutable();

        return AuditLogModel::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $before)
            ->delete();
    }

    public function deleteByIds(array $ids): int
    {
        $normalized = [];
        foreach ($ids as $id) {
            $v = $this->normalizeScalar($id);
            if ($v !== null) {
                $normalized[] = $v;
            }
        }

        if ($normalized === []) {
            return 0;
        }

        return AuditLogModel::query()->whereKey($normalized)->delete();
    }

    public function getStatistics(array $filters = []): array
    {
        $builder = AuditLogModel::query();
        $this->applyFilters($builder, $filters);

        $total = (clone $builder)->count();

        $byLogName = (clone $builder)
            ->selectRaw('log_name, COUNT(*) as count')
            ->groupBy('log_name')
            ->pluck('count', 'log_name')
            ->all();

        $byLevel = (clone $builder)
            ->selectRaw('level, COUNT(*) as count')
            ->groupBy('level')
            ->pluck('count', 'level')
            ->all();

        $byEvent = (clone $builder)
            ->selectRaw('event, COUNT(*) as count')
            ->groupBy('event')
            ->pluck('count', 'event')
            ->all();

        return [
            'total_count' => $total,
            'by_log_name' => $byLogName,
            'by_level' => $byLevel,
            'by_event' => $byEvent,
            'by_date' => [],
        ];
    }

    public function exportToArray(array $filters = [], int $limit = 10000): array
    {
        $limit = $limit < 1 ? 1 : $limit;

        $builder = AuditLogModel::query();
        $this->applyFilters($builder, $filters);

        $rows = [];
        $count = 0;
        foreach (
            $builder
            ->orderBy('created_at', 'desc')
            ->cursor() as $log
        ) {
            if ($count >= $limit) {
                break;
            }

            if ($log instanceof AuditLogModel) {
                $rows[] = $log->toArray();
                $count++;
            }
        }

        return $rows;
    }

    /**
     * @param Builder<AuditLogModel> $builder
     * @param array<string, mixed> $filters
     */
    private function applyFilters(Builder $builder, array $filters): void
    {
        $logName = $this->normalizeString($filters['log_name'] ?? null);
        if ($logName !== null) {
            $builder->where('log_name', $logName);
        }

        $event = $this->normalizeString($filters['event'] ?? null);
        if ($event !== null) {
            $builder->where('event', $event);
        }

        $level = $this->normalizeInt($filters['level'] ?? null);
        if ($level !== null) {
            $builder->where('level', $level);
        }

        $subjectType = $this->normalizeString($filters['subject_type'] ?? null);
        if ($subjectType !== null) {
            $builder->where('subject_type', $subjectType);
        }

        $subjectId = $this->normalizeScalar($filters['subject_id'] ?? null);
        if ($subjectId !== null) {
            $builder->where('subject_id', $subjectId);
        }

        $causerType = $this->normalizeString($filters['causer_type'] ?? null);
        if ($causerType !== null) {
            $builder->where('causer_type', $causerType);
        }

        $causerId = $this->normalizeScalar($filters['causer_id'] ?? null);
        if ($causerId !== null) {
            $builder->where('causer_id', $causerId);
        }

        $tenantId = $this->normalizeScalar($filters['tenant_id'] ?? null);
        if ($tenantId !== null) {
            $builder->where('tenant_id', $tenantId);
        }

        $batchUuid = $this->normalizeString($filters['batch_uuid'] ?? null);
        if ($batchUuid !== null) {
            $builder->where('batch_uuid', $batchUuid);
        }

        $dateFrom = $this->normalizeDateTime($filters['date_from'] ?? null);
        if ($dateFrom !== null) {
            $builder->where('created_at', '>=', $dateFrom);
        }

        $dateTo = $this->normalizeDateTime($filters['date_to'] ?? null);
        if ($dateTo !== null) {
            $builder->where('created_at', '<=', $dateTo);
        }

        $search = $this->normalizeString($filters['search'] ?? null);
        if ($search !== null) {
            $builder->where(static function (Builder $q) use ($search): void {
                $q->where('log_name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('event', 'like', '%' . $search . '%')
                    ->orWhere('subject_id', 'like', '%' . $search . '%')
                    ->orWhere('causer_id', 'like', '%' . $search . '%');
            });
        }
    }

    private function normalizeString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $v = trim($value);

        return $v === '' ? null : $v;
    }

    private function normalizeScalar(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_scalar($value)) {
            $v = trim((string) $value);
            return $v === '' ? null : $v;
        }

        return null;
    }

    private function normalizeInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '' && ctype_digit(trim($value))) {
            return (int) trim($value);
        }

        return null;
    }

    private function normalizeDateTime(mixed $value): ?\DateTimeInterface
    {
        if ($value instanceof \DateTimeInterface) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            try {
                return new \DateTimeImmutable($value);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}
