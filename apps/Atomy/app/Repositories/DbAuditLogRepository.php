<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\AuditLog;
use Nexus\AuditLogger\Contracts\AuditLogInterface;
use Nexus\AuditLogger\Contracts\AuditLogRepositoryInterface;

/**
 * Eloquent implementation of AuditLogRepositoryInterface
 * Satisfies: ARC-AUD-0007 (Repository implementations in application layer)
 *
 * @package App\Repositories
 */
class DbAuditLogRepository implements AuditLogRepositoryInterface
{
    public function create(array $data): AuditLogInterface
    {
        return AuditLog::create($data);
    }

    public function findById($id): ?AuditLogInterface
    {
        return AuditLog::find($id);
    }

    public function search(
        array $filters = [],
        int $page = 1,
        int $perPage = 50,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc'
    ): array {
        $query = AuditLog::query();

        // Apply filters
        if (!empty($filters['log_name'])) {
            $query->where('log_name', $filters['log_name']);
        }

        if (!empty($filters['description'])) {
            $query->where('description', 'like', "%{$filters['description']}%");
        }

        if (!empty($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        if (!empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }

        if (!empty($filters['causer_type'])) {
            $query->where('causer_type', $filters['causer_type']);
        }

        if (!empty($filters['causer_id'])) {
            $query->where('causer_id', $filters['causer_id']);
        }

        if (!empty($filters['event'])) {
            $query->where('event', $filters['event']);
        }

        if (isset($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        if (!empty($filters['batch_uuid'])) {
            $query->where('batch_uuid', $filters['batch_uuid']);
        }

        if (!empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        // Full-text search per FUN-AUD-0189
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('log_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('properties', 'like', "%{$search}%");
            });
        }

        // Expired filter
        if (isset($filters['expired_only']) && $filters['expired_only']) {
            $query->expired();
        }

        // Get total count before pagination
        $total = $query->count();

        // Apply sorting and pagination
        $data = $query->orderBy($sortBy, $sortDirection)
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return [
            'data' => $data->all(),
            'total' => $total,
        ];
    }

    public function getBySubject(string $subjectType, $subjectId, int $limit = 100): array
    {
        return AuditLog::forSubject($subjectType, $subjectId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function getByCauser(string $causerType, $causerId, int $limit = 100): array
    {
        return AuditLog::forCauser($causerType, $causerId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function getByBatchUuid(string $batchUuid): array
    {
        return AuditLog::byBatch($batchUuid)
            ->orderBy('created_at', 'asc')
            ->get()
            ->all();
    }

    public function getByLevel(int $level, int $limit = 100): array
    {
        return AuditLog::byLevel($level)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function getByTenant($tenantId, int $limit = 100): array
    {
        return AuditLog::forTenant($tenantId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function getExpired(?\DateTimeInterface $beforeDate = null, int $limit = 1000): array
    {
        return AuditLog::expired($beforeDate)
            ->orderBy('expires_at', 'asc')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function deleteExpired(?\DateTimeInterface $beforeDate = null): int
    {
        return AuditLog::expired($beforeDate)->delete();
    }

    public function deleteByIds(array $ids): int
    {
        return AuditLog::whereIn('id', $ids)->delete();
    }

    public function getStatistics(array $filters = []): array
    {
        $query = AuditLog::query();

        // Apply same filters as search
        $this->applyFilters($query, $filters);

        $totalCount = $query->count();

        $byLogName = $query->clone()
            ->selectRaw('log_name, COUNT(*) as count')
            ->groupBy('log_name')
            ->pluck('count', 'log_name')
            ->toArray();

        $byLevel = $query->clone()
            ->selectRaw('level, COUNT(*) as count')
            ->groupBy('level')
            ->pluck('count', 'level')
            ->toArray();

        $byEvent = $query->clone()
            ->selectRaw('event, COUNT(*) as count')
            ->whereNotNull('event')
            ->groupBy('event')
            ->pluck('count', 'event')
            ->toArray();

        $byDate = $query->clone()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->pluck('count', 'date')
            ->toArray();

        return [
            'total_count' => $totalCount,
            'by_log_name' => $byLogName,
            'by_level' => $byLevel,
            'by_event' => $byEvent,
            'by_date' => $byDate,
        ];
    }

    public function exportToArray(array $filters = [], int $limit = 10000): array
    {
        $query = AuditLog::query();
        $this->applyFilters($query, $filters);

        return $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'log_name' => $log->log_name,
                    'description' => $log->description,
                    'subject_type' => $log->subject_type,
                    'subject_id' => $log->subject_id,
                    'causer_type' => $log->causer_type,
                    'causer_id' => $log->causer_id,
                    'event' => $log->event,
                    'level' => $log->level,
                    'batch_uuid' => $log->batch_uuid,
                    'ip_address' => $log->ip_address,
                    'tenant_id' => $log->tenant_id,
                    'created_at' => $log->created_at->toIso8601String(),
                    'expires_at' => $log->expires_at->toIso8601String(),
                ];
            })
            ->toArray();
    }

    private function applyFilters($query, array $filters): void
    {
        if (!empty($filters['log_name'])) {
            $query->where('log_name', $filters['log_name']);
        }

        if (!empty($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        if (!empty($filters['causer_type'])) {
            $query->where('causer_type', $filters['causer_type']);
        }

        if (isset($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        if (!empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['expired_only']) && $filters['expired_only']) {
            $query->expired();
        }
    }
}
