<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class VendorInvitationController extends Controller
{
    use ExtractsAuthContext;

    public function index(Request $request, string $rfqId): JsonResponse
    {
        // TODO: tenant scoping via $this->tenantId($request)

        return response()->json([
            'data' => [],
        ]);
    }

    public function store(Request $request, string $rfqId): JsonResponse
    {
        // TODO: tenant scoping via $this->tenantId($request)

        return response()->json([
            'data' => [
                'id' => 'stub-invitation-id',
                'rfq_id' => $rfqId,
                'status' => 'pending',
            ],
        ], 201);
    }

    public function remind(Request $request, string $rfqId, string $invId): JsonResponse
    {
        // TODO: tenant scoping via $this->tenantId($request)

        return response()->json([
            'data' => [
                'id' => $invId,
                'rfq_id' => $rfqId,
                'status' => 'pending',
            ],
        ]);
    }
}
