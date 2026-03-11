<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DecisionTrailController extends Controller
{
    use ExtractsAuthContext;

    /** GET /decision-trail */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $pagination = $this->paginationParams($request);

        return response()->json([
            'data' => [],
            'meta' => [
                'current_page' => $pagination['page'],
                'per_page' => $pagination['per_page'],
                'total' => 0,
                'filters' => [
                    'scope' => $request->query('scope'),
                    'type' => $request->query('type'),
                    'date_from' => $request->query('date_from'),
                    'date_to' => $request->query('date_to'),
                ],
            ],
        ]);
    }

    /** GET /decision-trail/{id} */
    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        return response()->json([
            'data' => [
                'id' => $id,
                'tenant_id' => $tenantId,
                'scope' => '',
                'event_type' => '',
                'actor_id' => '',
                'description' => '',
                'metadata' => [],
                'hash' => '',
                'previous_hash' => '',
                'created_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /** POST /decision-trail/verify */
    public function verify(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        return response()->json([
            'data' => [
                'verified' => true,
                'entries_checked' => 0,
                'integrity_status' => 'valid',
                'verified_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /** POST /decision-trail/export */
    public function export(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        return response()->json([
            'data' => [
                'export_id' => 'stub-export-id',
                'format' => $request->input('format', 'json'),
                'status' => 'processing',
            ],
        ]);
    }
}
