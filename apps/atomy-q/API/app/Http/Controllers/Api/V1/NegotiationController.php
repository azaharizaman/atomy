<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NegotiationController extends Controller
{
    use ExtractsAuthContext;

    /**
     * GET /negotiations
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
     * POST /negotiations/rounds
     *
     * Start a new negotiation round. Returns 201.
     */
    public function startRound(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => 'stub-round-id',
                'rfq_id' => 'stub-rfq-id',
                'round_number' => 1,
                'status' => 'open',
                'created_at' => null,
            ],
        ], 201);
    }

    /**
     * POST /negotiations/rounds/:roundId/counter-offer
     */
    public function counterOffer(Request $request, string $roundId): JsonResponse
    {
        return response()->json([
            'data' => [
                'round_id' => $roundId,
                'counter_offer_submitted_at' => null,
            ],
        ]);
    }

    /**
     * POST /negotiations/:rfqId/bafo
     *
     * Best and Final Offer
     */
    public function bafo(Request $request, string $rfqId): JsonResponse
    {
        return response()->json([
            'data' => [
                'rfq_id' => $rfqId,
                'bafo_requested_at' => null,
            ],
        ]);
    }

    /**
     * POST /negotiations/:rfqId/close
     */
    public function close(Request $request, string $rfqId): JsonResponse
    {
        return response()->json([
            'data' => [
                'rfq_id' => $rfqId,
                'status' => 'closed',
                'closed_at' => null,
            ],
        ]);
    }
}
