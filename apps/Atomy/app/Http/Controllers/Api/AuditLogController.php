<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\AuditLogger\Services\AuditLogManager;
use Nexus\AuditLogger\Services\AuditLogSearchService;
use Nexus\AuditLogger\Services\AuditLogExportService;

/**
 * API Controller for Audit Logs
 * Satisfies: FUN-AUD-0198 (RESTful API endpoints)
 *
 * @package App\Http\Controllers\Api
 */
class AuditLogController extends Controller
{
    public function __construct(
        private AuditLogSearchService $searchService,
        private AuditLogExportService $exportService,
        private AuditLogManager $auditManager
    ) {}

    /**
     * List/search audit logs
     * GET /api/v1/audit-logs
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'log_name',
            'description',
            'subject_type',
            'subject_id',
            'causer_type',
            'causer_id',
            'event',
            'level',
            'batch_uuid',
            'tenant_id',
            'date_from',
            'date_to',
            'search',
        ]);

        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 50);
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        $result = $this->searchService->search(
            $filters,
            $page,
            $perPage,
            $sortBy,
            $sortDirection
        );

        return response()->json([
            'data' => $result['data'],
            'meta' => [
                'total' => $result['total'],
                'page' => $page,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Get single audit log
     * GET /api/v1/audit-logs/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $log = $this->searchService->search(['id' => $id]);

        if (empty($log['data'])) {
            return response()->json([
                'message' => 'Audit log not found'
            ], 404);
        }

        return response()->json([
            'data' => $log['data'][0]
        ]);
    }

    /**
     * Get audit history for a subject entity
     * GET /api/v1/audit-logs/subject/{type}/{id}
     *
     * @param string $type
     * @param int|string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function subjectHistory(string $type, int|string $id, Request $request): JsonResponse
    {
        $limit = $request->get('limit', 100);
        $logs = $this->searchService->searchBySubject($type, $id, $limit);

        return response()->json([
            'data' => $logs
        ]);
    }

    /**
     * Get audit activity for a causer (user)
     * GET /api/v1/audit-logs/causer/{type}/{id}
     *
     * @param string $type
     * @param int|string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function causerActivity(string $type, int|string $id, Request $request): JsonResponse
    {
        $limit = $request->get('limit', 100);
        $logs = $this->searchService->searchByCauser($type, $id, $limit);

        return response()->json([
            'data' => $logs
        ]);
    }

    /**
     * Get audit logs by batch UUID
     * GET /api/v1/audit-logs/batch/{uuid}
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function batchLogs(string $uuid): JsonResponse
    {
        $logs = $this->searchService->searchByBatch($uuid);

        return response()->json([
            'data' => $logs
        ]);
    }

    /**
     * Get audit statistics
     * GET /api/v1/audit-logs/statistics
     * Satisfies: FUN-AUD-0199
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        $filters = $request->only([
            'log_name',
            'subject_type',
            'causer_type',
            'level',
            'tenant_id',
            'date_from',
            'date_to',
        ]);

        $stats = $this->searchService->getStatistics($filters);

        return response()->json([
            'data' => $stats
        ]);
    }

    /**
     * Export audit logs
     * GET /api/v1/audit-logs/export
     * Satisfies: FUN-AUD-0191
     *
     * @param Request $request
     * @return mixed
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'json');
        $filters = $request->only([
            'log_name',
            'subject_type',
            'causer_type',
            'level',
            'tenant_id',
            'date_from',
            'date_to',
        ]);
        $limit = $request->get('limit', 10000);

        $filename = 'audit-logs-' . date('Y-m-d-His');

        return match($format) {
            'csv' => response($this->exportService->exportToCsv($filters, $limit))
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}.csv\""),
            
            'json' => response($this->exportService->exportToJson($filters, $limit))
                ->header('Content-Type', 'application/json')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}.json\""),
            
            default => response()->json([
                'message' => 'Invalid format. Supported: csv, json'
            ], 400)
        };
    }

    /**
     * Manually create audit log
     * POST /api/v1/audit-logs
     * (For system activities or manual logging)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'log_name' => 'required|string|max:255',
            'description' => 'required|string',
            'subject_type' => 'nullable|string|max:255',
            'subject_id' => 'nullable|integer',
            'properties' => 'nullable|array',
            'event' => 'nullable|string|max:255',
            'level' => 'nullable|integer|min:1|max:4',
            'batch_uuid' => 'nullable|uuid',
        ]);

        $log = $this->auditManager->log(
            logName: $validated['log_name'],
            description: $validated['description'],
            subjectType: $validated['subject_type'] ?? null,
            subjectId: $validated['subject_id'] ?? null,
            properties: $validated['properties'] ?? [],
            level: $validated['level'] ?? null,
            event: $validated['event'] ?? null,
            batchUuid: $validated['batch_uuid'] ?? null,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            tenantId: auth()->user()->tenant_id ?? null
        );

        return response()->json([
            'message' => 'Audit log created successfully',
            'data' => $log
        ], 201);
    }
}
