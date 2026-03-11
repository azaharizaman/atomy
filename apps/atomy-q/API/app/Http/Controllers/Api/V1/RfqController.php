<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RfqController extends Controller
{
    use ExtractsAuthContext;

    public function index(Request $request): JsonResponse
    {
        // TODO: tenant scoping via $this->tenantId($request)
        // Accept: status, owner, category, search, page, per_page

        $params = $this->paginationParams($request);

        return response()->json([
            'data' => [],
            'meta' => [
                'current_page' => $params['page'],
                'per_page' => $params['per_page'],
                'total' => 0,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        // TODO: tenant scoping via $this->tenantId($request)

        return response()->json([
            'data' => [
                'id' => 'stub-id',
                'status' => 'draft',
            ],
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        // TODO: tenant scoping via $this->tenantId($request)

        return response()->json([
            'data' => [
                'id' => $id,
                'status' => 'draft',
            ],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        // TODO: tenant scoping via $this->tenantId($request)

        return response()->json([
            'data' => [
                'id' => $id,
                'status' => 'draft',
            ],
        ]);
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        // TODO: tenant scoping via $this->tenantId($request)

        return response()->json([
            'data' => [
                'id' => $id,
                'status' => 'submitted',
            ],
        ]);
    }

    public function duplicate(Request $request, string $id): JsonResponse
    {
        // TODO: tenant scoping via $this->tenantId($request)

        return response()->json([
            'data' => [
                'id' => 'stub-duplicate-id',
                'status' => 'draft',
            ],
        ], 201);
    }

    public function saveDraft(Request $request, string $id): JsonResponse
    {
        // TODO: tenant scoping via $this->tenantId($request)

        return response()->json([
            'data' => [
                'id' => $id,
                'status' => 'draft',
            ],
        ]);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        // TODO: tenant scoping via $this->tenantId($request)
        // Bulk close/archive/assign

        return response()->json([
            'data' => [
                'affected' => 0,
            ],
        ]);
    }

    public function lineItems(Request $request, string $rfqId): JsonResponse
    {
        // TODO: tenant scoping via $this->tenantId($request)

        return response()->json([
            'data' => [],
        ]);
    }

    public function storeLineItem(Request $request, string $rfqId): JsonResponse
    {
        // TODO: tenant scoping via $this->tenantId($request)

        return response()->json([
            'data' => [
                'id' => 'stub-line-item-id',
                'rfq_id' => $rfqId,
            ],
        ], 201);
    }

    public function updateLineItem(Request $request, string $rfqId, string $itemId): JsonResponse
    {
        // TODO: tenant scoping via $this->tenantId($request)

        return response()->json([
            'data' => [
                'id' => $itemId,
                'rfq_id' => $rfqId,
            ],
        ]);
    }

    public function destroyLineItem(Request $request, string $rfqId, string $itemId): JsonResponse
    {
        // TODO: tenant scoping via $this->tenantId($request)

        return response()->json([], 204);
    }
}
