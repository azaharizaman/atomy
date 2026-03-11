<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class HandoffController extends Controller
{
    use ExtractsAuthContext;

    /**
     * GET /handoffs
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [],
        ]);
    }

    /**
     * GET /handoffs/:id
     */
    public function show(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'status' => 'pending',
                'created_at' => null,
            ],
        ]);
    }

    /**
     * POST /handoffs/:id/validate
     *
     * Method name validate_ to avoid reserved word.
     */
    public function validate_(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'valid' => true,
                'errors' => [],
            ],
        ]);
    }

    /**
     * POST /handoffs/:id/send
     */
    public function send(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'status' => 'sent',
                'sent_at' => null,
            ],
        ]);
    }

    /**
     * POST /handoffs/:id/retry
     */
    public function retry(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'status' => 'retrying',
                'retried_at' => null,
            ],
        ]);
    }

    /**
     * GET /handoffs/destinations
     */
    public function destinations(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [],
        ]);
    }
}
