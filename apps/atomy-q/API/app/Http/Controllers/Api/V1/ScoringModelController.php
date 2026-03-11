<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ScoringModelController extends Controller
{
    use ExtractsAuthContext;

    /** GET /scoring-models */
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
            ],
        ]);
    }

    /** POST /scoring-models */
    public function store(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        return response()->json([
            'data' => [
                'id' => 'stub-scoring-model-id',
                'tenant_id' => $tenantId,
                'name' => $request->input('name', ''),
                'status' => 'draft',
            ],
        ], 201);
    }

    /** GET /scoring-models/{id} */
    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        return response()->json([
            'data' => [
                'id' => $id,
                'tenant_id' => $tenantId,
                'name' => '',
                'description' => '',
                'status' => 'draft',
                'criteria' => [],
                'weights' => [],
                'version' => 1,
            ],
        ]);
    }

    /** PUT /scoring-models/{id} */
    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        return response()->json([
            'data' => [
                'id' => $id,
                'tenant_id' => $tenantId,
                'updated' => true,
            ],
        ]);
    }

    /** POST /scoring-models/{id}/publish */
    public function publish(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        return response()->json([
            'data' => [
                'id' => $id,
                'tenant_id' => $tenantId,
                'status' => 'published',
            ],
        ]);
    }

    /** GET /scoring-models/{id}/versions */
    public function versions(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        return response()->json(['data' => []]);
    }

    /** PUT /scoring-models/{id}/assignments */
    public function updateAssignments(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        return response()->json([
            'data' => [
                'id' => $id,
                'assignments' => $request->input('assignments', []),
            ],
        ]);
    }

    /** POST /scoring-models/{id}/preview */
    public function preview(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        return response()->json([
            'data' => [
                'model_id' => $id,
                'preview_scores' => [],
            ],
        ]);
    }
}
