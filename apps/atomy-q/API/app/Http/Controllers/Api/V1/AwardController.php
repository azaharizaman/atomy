<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AwardController extends Controller
{
    use ExtractsAuthContext;

    /**
     * GET /awards
     *
     * Query: rfqId=:id
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [],
        ]);
    }

    /**
     * POST /awards
     *
     * Create an award. Returns 201.
     */
    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => 'stub-award-id',
                'rfq_id' => 'stub-rfq-id',
                'status' => 'draft',
                'created_at' => null,
            ],
        ], 201);
    }

    /**
     * PUT /awards/:id/split
     */
    public function updateSplit(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'splits' => [],
                'updated_at' => null,
            ],
        ]);
    }

    /**
     * POST /awards/:id/debrief/:vendorId
     */
    public function debrief(Request $request, string $id, string $vendorId): JsonResponse
    {
        return response()->json([
            'data' => [
                'award_id' => $id,
                'vendor_id' => $vendorId,
                'debriefed_at' => null,
            ],
        ]);
    }

    /**
     * POST /awards/:id/protest
     */
    public function protest(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'award_id' => $id,
                'protest_id' => 'stub-protest-id',
                'created_at' => null,
            ],
        ], 201);
    }

    /**
     * PATCH /awards/:id/protest/:protestId/resolve
     */
    public function resolveProtest(Request $request, string $id, string $protestId): JsonResponse
    {
        return response()->json([
            'data' => [
                'award_id' => $id,
                'protest_id' => $protestId,
                'status' => 'resolved',
                'resolved_at' => null,
            ],
        ]);
    }

    /**
     * POST /awards/:id/signoff
     */
    public function signoff(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'status' => 'signed_off',
                'signed_off_at' => null,
            ],
        ]);
    }
}
