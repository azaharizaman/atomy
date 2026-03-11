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
        return $this->notImplemented('Listing awards', [
            'tenant_id' => $this->tenantId($request),
        ]);
    }

    /**
     * POST /awards
     *
     * Create an award. Returns 201.
     */
    public function store(Request $request): JsonResponse
    {
        return $this->notImplemented('Creating awards', [
            'tenant_id' => $this->tenantId($request),
        ]);
    }

    /**
     * PUT /awards/:id/split
     */
    public function updateSplit(Request $request, string $id): JsonResponse
    {
        return $this->notImplemented('Updating award split', [
            'tenant_id' => $this->tenantId($request),
            'award_id' => $id,
        ]);
    }

    /**
     * POST /awards/:id/debrief/:vendorId
     */
    public function debrief(Request $request, string $id, string $vendorId): JsonResponse
    {
        return $this->notImplemented('Debriefing award vendor', [
            'tenant_id' => $this->tenantId($request),
            'award_id' => $id,
            'vendor_id' => $vendorId,
        ]);
    }

    /**
     * POST /awards/:id/protest
     */
    public function protest(Request $request, string $id): JsonResponse
    {
        return $this->notImplemented('Creating award protest', [
            'tenant_id' => $this->tenantId($request),
            'award_id' => $id,
        ]);
    }

    /**
     * PATCH /awards/:id/protest/:protestId/resolve
     */
    public function resolveProtest(Request $request, string $id, string $protestId): JsonResponse
    {
        return $this->notImplemented('Resolving award protest', [
            'tenant_id' => $this->tenantId($request),
            'award_id' => $id,
            'protest_id' => $protestId,
        ]);
    }

    /**
     * POST /awards/:id/signoff
     */
    public function signoff(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'error' => 'Not implemented',
            'message' => 'Signing off awards is not implemented yet.',
            'context' => [
                'tenant_id' => $this->tenantId($request),
                'award_id' => $id,
            ],
        ], 501);
    }

    /**
     * @param array<string, string> $context
     */
    private function notImplemented(string $operation, array $context = []): JsonResponse
    {
        return response()->json([
            'error' => 'Not implemented',
            'message' => $operation.' is not implemented yet.',
            'context' => $context,
        ], 501);
    }
}
