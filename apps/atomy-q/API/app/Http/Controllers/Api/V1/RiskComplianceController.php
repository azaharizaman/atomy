<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use App\Models\RiskItem;
use App\Models\Rfq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\InsightOperations\Coordinators\RiskInsightCoordinator;

final class RiskComplianceController extends Controller
{
    use ExtractsAuthContext;

    public function __construct(
        private readonly RiskInsightCoordinator $riskInsightCoordinator,
    ) {
    }

    /**
     * GET /risk-items?rfqId=:id
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $rfqId = trim((string) $request->query('rfqId'));
        if ($rfqId !== '' && ! $this->rfqExists($tenantId, $rfqId)) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }

        $result = $this->riskInsightCoordinator->show($tenantId, $rfqId)->toResponseArray();
        $result['meta'] = [
            'rfq_id' => $rfqId !== '' ? $rfqId : null,
        ];

        return response()->json($result);
    }

    /**
     * POST /risk-items/generate
     */
    public function generate(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $rfqId = trim((string) ($request->input('rfq_id') ?? $request->query('rfqId', '')));
        if ($rfqId === '' || ! $this->rfqExists($tenantId, $rfqId)) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }

        $result = $this->riskInsightCoordinator->generate($tenantId, $rfqId, $this->userId($request))->toResponseArray();
        $result['meta'] = [
            'rfq_id' => $rfqId,
        ];

        return response()->json($result);
    }

    /**
     * POST /risk-items/:id/escalate
     */
    public function escalate(Request $request, string $id): JsonResponse
    {
        $riskItem = $this->findRiskItem($this->tenantId($request), $id);
        if (! $riskItem instanceof RiskItem) {
            return response()->json(['message' => 'Risk item not found'], 404);
        }

        $riskItem->status = 'escalated';
        $riskItem->save();

        return response()->json(['data' => $this->serializeRiskItem($riskItem)]);
    }

    /**
     * POST /risk-items/:id/exception
     */
    public function exception(Request $request, string $id): JsonResponse
    {
        $riskItem = $this->findRiskItem($this->tenantId($request), $id);
        if (! $riskItem instanceof RiskItem) {
            return response()->json(['message' => 'Risk item not found'], 404);
        }

        $riskItem->status = 'exception';
        $riskItem->resolved_at = now();
        $riskItem->resolved_by = $this->userId($request);
        $riskItem->save();

        return response()->json(['data' => $this->serializeRiskItem($riskItem)]);
    }

    private function rfqExists(string $tenantId, string $rfqId): bool
    {
        return Rfq::query()
            ->where('tenant_id', strtolower(trim($tenantId)))
            ->where('id', strtolower(trim($rfqId)))
            ->exists();
    }

    private function findRiskItem(string $tenantId, string $id): ?RiskItem
    {
        return RiskItem::query()
            ->where('tenant_id', strtolower(trim($tenantId)))
            ->where('id', strtolower(trim($id)))
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeRiskItem(RiskItem $riskItem): array
    {
        return [
            'id' => (string) $riskItem->id,
            'rfq_id' => $riskItem->rfq_id,
            'severity' => (string) $riskItem->severity,
            'title' => (string) $riskItem->title,
            'description' => $riskItem->description,
            'source' => $riskItem->source,
            'status' => (string) $riskItem->status,
            'resolved_at' => $riskItem->resolved_at?->toAtomString(),
            'resolved_by' => $riskItem->resolved_by,
            'updated_at' => $riskItem->updated_at?->toAtomString(),
        ];
    }
}
