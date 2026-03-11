<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ScenarioController extends Controller
{
    use ExtractsAuthContext;

    /**
     * GET /scenarios?rfqId=:id
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [],
            'meta' => [
                'rfq_id' => $request->query('rfqId'),
            ],
        ]);
    }

    /**
     * POST /scenarios
     */
    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => 'stub-scenario-id',
                'name' => 'Stub Scenario',
                'rfq_id' => 'stub-rfq-id',
            ],
        ], 201);
    }

    /**
     * PUT /scenarios/:id
     */
    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'name' => 'Stub Scenario',
                'rfq_id' => 'stub-rfq-id',
            ],
        ]);
    }

    /**
     * DELETE /scenarios/:id
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        return response()->json([], 204);
    }

    /**
     * POST /scenarios/compare
     */
    public function compare(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'comparison' => [],
                'scenarios' => [],
            ],
        ]);
    }
}
