<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\ComparisonFinalizeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ComparisonRunController extends Controller
{
    use ExtractsAuthContext;

    /**
     * GET /comparison-runs
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
     * GET /comparison-runs/:id
     * Scoped by tenant_id.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'rfq_id' => null,
                'status' => 'draft',
            ],
        ]);
    }

    /**
     * POST /comparison-runs/preview
     * Scoped by tenant_id.
     */
    public function preview(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => 'cr-preview-' . uniqid(),
                'status' => 'preview',
            ],
        ], 201);
    }

    /**
     * POST /comparison-runs/final
     * Method named final_ since final is reserved in PHP.
     * Scoped by tenant_id.
     */
    public function final_(ComparisonFinalizeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return response()->json([
            'data' => [
                'id' => 'cr-' . uniqid(),
                'rfq_id' => $validated['rfq_id'],
                'status' => 'ready_for_approval',
            ],
        ], 201);
    }

    /**
     * GET /comparison-runs/:id/matrix
     * Scoped by tenant_id.
     */
    public function matrix(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'matrix' => [],
                'headers' => [],
            ],
        ]);
    }

    /**
     * GET /comparison-runs/:id/readiness
     * Scoped by tenant_id.
     */
    public function readiness(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'ready' => false,
                'issues' => [],
            ],
        ]);
    }

    /**
     * PATCH /comparison-runs/:id/scoring-model
     * Scoped by tenant_id.
     */
    public function updateScoringModel(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'scoring_model' => [],
            ],
        ]);
    }

    /**
     * POST /comparison-runs/:id/lock
     * Scoped by tenant_id.
     */
    public function lock(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'locked' => true,
            ],
        ]);
    }

    /**
     * POST /comparison-runs/:id/unlock
     * Scoped by tenant_id.
     */
    public function unlock(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'locked' => false,
            ],
        ]);
    }
}
