<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\NormalizationOverrideRequest;
use App\Http\Requests\NormalizationResolveConflictRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NormalizationController extends Controller
{
    use ExtractsAuthContext;

    /** GET /normalization/{rfqId}/source-lines */
    public function sourceLines(Request $request, string $rfqId): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        return response()->json([
            'data' => [],
            'meta' => ['rfq_id' => $rfqId],
        ]);
    }

    /** GET /normalization/{rfqId}/normalized-items */
    public function normalizedItems(Request $request, string $rfqId): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        return response()->json([
            'data' => [],
            'meta' => ['rfq_id' => $rfqId],
        ]);
    }

    /** PUT /normalization/source-lines/{id}/mapping */
    public function updateMapping(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        return response()->json([
            'data' => [
                'id' => $id,
                'rfq_line_id' => $request->input('rfq_line_id'),
                'mapped' => true,
            ],
        ]);
    }

    /** POST /normalization/{rfqId}/bulk-mapping */
    public function bulkMapping(Request $request, string $rfqId): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        return response()->json([
            'data' => [
                'rfq_id' => $rfqId,
                'mapped_count' => 0,
            ],
        ]);
    }

    /** PUT /normalization/source-lines/{id}/override */
    public function override(NormalizationOverrideRequest $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $validated = $request->validated();

        return response()->json([
            'data' => [
                'id' => $id,
                'is_overridden' => true,
                'override_data' => $validated['override_data'],
                'issue_code' => $validated['issue_code'] ?? null,
            ],
        ]);
    }

    /** DELETE /normalization/source-lines/{id}/override */
    public function revertOverride(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        return response()->json(null, 204);
    }

    /** GET /normalization/{rfqId}/conflicts */
    public function conflicts(Request $request, string $rfqId): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        return response()->json([
            'data' => [],
            'meta' => ['rfq_id' => $rfqId],
        ]);
    }

    /** PUT /normalization/conflicts/{id}/resolve */
    public function resolveConflict(NormalizationResolveConflictRequest $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $validated = $request->validated();

        return response()->json([
            'data' => [
                'id' => $id,
                'status' => 'resolved',
                'resolution' => $validated['resolution'],
                'resolution_data' => $validated['resolution_data'] ?? [],
                'issue_code' => $validated['issue_code'] ?? null,
            ],
        ]);
    }

    /** POST /normalization/{rfqId}/lock */
    public function lock(Request $request, string $rfqId): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        return response()->json([
            'data' => [
                'rfq_id' => $rfqId,
                'locked' => true,
                'locked_by' => $this->userId($request),
            ],
        ]);
    }

    /** POST /normalization/{rfqId}/unlock */
    public function unlock(Request $request, string $rfqId): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        return response()->json([
            'data' => [
                'rfq_id' => $rfqId,
                'locked' => false,
            ],
        ]);
    }
}
