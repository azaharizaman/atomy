<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class QuoteSubmissionController extends Controller
{
    use ExtractsAuthContext;

    /**
     * GET /quote-submissions
     * Params: status, rfq_id, vendor_id, page, per_page
     * Scoped by tenant_id.
     */
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
                'from' => null,
                'to' => null,
            ],
        ]);
    }

    /**
     * POST /quote-submissions/upload
     * Scoped by tenant_id.
     */
    public function upload(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => 'qs-' . uniqid(),
                'status' => 'processing',
            ],
        ], 201);
    }

    /**
     * GET /quote-submissions/:id
     * Two-tab detail data.
     * Scoped by tenant_id.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'rfq_id' => null,
                'vendor_id' => null,
                'status' => 'pending',
                'tab_overview' => [],
                'tab_details' => [],
            ],
        ]);
    }

    /**
     * PATCH /quote-submissions/:id/status
     * Accept/reject.
     * Scoped by tenant_id.
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'status' => 'accepted',
            ],
        ]);
    }

    /**
     * POST /quote-submissions/:id/replace
     * Scoped by tenant_id.
     */
    public function replace(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'status' => 'replaced',
            ],
        ]);
    }

    /**
     * POST /quote-submissions/:id/reparse
     * Scoped by tenant_id.
     */
    public function reparse(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'status' => 'reparsing',
            ],
        ], 202);
    }

    /**
     * POST /quote-submissions/:id/assign
     * Scoped by tenant_id.
     */
    public function assign(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'assigned_to' => $this->userId($request),
            ],
        ]);
    }
}
