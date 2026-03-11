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
        return $this->notImplemented('Listing negotiations', [
            'tenant_id' => $this->tenantId($request),
        ]);
    }

    /**
     * POST /negotiations/rounds
     *
     * Start a new negotiation round. Returns 201.
     */
    public function startRound(Request $request): JsonResponse
    {
        return $this->notImplemented('Starting negotiation round', [
            'tenant_id' => $this->tenantId($request),
        ]);
    }

    /**
     * POST /negotiations/rounds/:roundId/counter-offer
     */
    public function counterOffer(Request $request, string $roundId): JsonResponse
    {
        return $this->notImplemented('Submitting counter offer', [
            'tenant_id' => $this->tenantId($request),
            'round_id' => $roundId,
        ]);
    }

    /**
     * POST /negotiations/:rfqId/bafo
     *
     * Best and Final Offer
     */
    public function bafo(Request $request, string $rfqId): JsonResponse
    {
        return $this->notImplemented('Requesting BAFO', [
            'tenant_id' => $this->tenantId($request),
            'rfq_id' => $rfqId,
        ]);
    }

    /**
     * POST /negotiations/:rfqId/close
     */
    public function close(Request $request, string $rfqId): JsonResponse
    {
        return $this->notImplemented('Closing negotiations', [
            'tenant_id' => $this->tenantId($request),
            'rfq_id' => $rfqId,
        ]);
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
