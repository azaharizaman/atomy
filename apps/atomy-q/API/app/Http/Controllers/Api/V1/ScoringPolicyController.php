<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ScoringPolicyController extends Controller
{
    use ExtractsAuthContext;

    /**
     * GET /scoring-policies
     */
    public function index(Request $request): JsonResponse
    {
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

    /**
     * POST /scoring-policies
     */
    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => 'stub-scoring-policy-id',
                'name' => 'Stub Policy',
                'status' => 'draft',
                'version' => 1,
            ],
        ], 201);
    }

    /**
     * GET /scoring-policies/:id
     */
    public function show(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'name' => 'Stub Policy',
                'status' => 'draft',
                'version' => 1,
                'rules' => [],
            ],
        ]);
    }

    /**
     * PUT /scoring-policies/:id
     */
    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'name' => 'Stub Policy',
                'status' => 'draft',
                'version' => 1,
            ],
        ]);
    }

    /**
     * POST /scoring-policies/:id/publish
     */
    public function publish(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'status' => 'published',
                'version' => 1,
            ],
        ]);
    }

    /**
     * PATCH /scoring-policies/:id/status
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'status' => 'active',
            ],
        ]);
    }

    /**
     * PUT /scoring-policies/:id/assignments
     */
    public function updateAssignments(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'assignments' => [],
            ],
        ]);
    }

    /**
     * GET /scoring-policies/:id/versions
     */
    public function versions(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                [
                    'version' => 1,
                    'status' => 'published',
                    'published_at' => null,
                ],
            ],
        ]);
    }
}
