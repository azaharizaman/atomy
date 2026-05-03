<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use App\Models\Rfq;
use App\Services\EvidenceVault\EvidenceVaultSummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class EvidenceVaultController extends Controller
{
    use ExtractsAuthContext;

    public function __construct(private readonly EvidenceVaultSummaryService $summaryService)
    {
    }

    public function show(Request $request, string $rfqId): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $rfq = Rfq::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($rfqId)
            ->firstOrFail();

        return response()->json([
            'data' => $this->summaryService->summarize($tenantId, $rfq),
        ]);
    }

    public function storeSupportingEvidence(Request $request, string $rfqId): JsonResponse
    {
        return $this->notImplemented();
    }

    public function finalizeAwardPack(Request $request, string $rfqId): JsonResponse
    {
        return $this->notImplemented();
    }

    public function exportAwardPack(Request $request, string $rfqId): JsonResponse
    {
        return $this->notImplemented();
    }

    private function notImplemented(): JsonResponse
    {
        return response()->json([
            'message' => 'Evidence Vault action is not implemented yet.',
        ], 501);
    }
}
