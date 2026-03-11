<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class VendorController extends Controller
{
    use ExtractsAuthContext;

    public function index(Request $request): JsonResponse
    {
        // TODO: tenant scoping via $this->tenantId($request)

        return response()->json([
            'data' => [],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        // TODO: tenant scoping via $this->tenantId($request)

        return response()->json([
            'data' => [
                'id' => $id,
                'name' => 'Stub Vendor',
            ],
        ]);
    }

    public function performance(Request $request, string $id): JsonResponse
    {
        // TODO: tenant scoping via $this->tenantId($request)

        return response()->json([
            'data' => [
                'vendor_id' => $id,
                'score' => 0,
            ],
        ]);
    }

    public function compliance(Request $request, string $id): JsonResponse
    {
        // TODO: tenant scoping via $this->tenantId($request)

        return response()->json([
            'data' => [
                'vendor_id' => $id,
                'status' => 'compliant',
            ],
        ]);
    }

    public function history(Request $request, string $id): JsonResponse
    {
        // TODO: tenant scoping via $this->tenantId($request)

        return response()->json([
            'data' => [],
        ]);
    }
}
