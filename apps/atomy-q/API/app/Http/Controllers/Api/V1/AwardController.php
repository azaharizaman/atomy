<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use App\Models\Award;
use App\Models\ComparisonRun;
use App\Models\QuoteSubmission;
use App\Models\Rfq;
use App\Models\VendorInvitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
        $tenantId = $this->tenantId($request);
        $pagination = $this->paginationParams($request);

        $query = Award::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at');

        $rfqFilter = $request->query('rfq_id', $request->query('rfqId'));
        if (is_string($rfqFilter) && $rfqFilter !== '') {
            $query->where('rfq_id', $rfqFilter);
        }

        $total = $query->count();
        $awards = $query
            ->forPage($pagination['page'], $pagination['per_page'])
            ->get();

        return response()->json([
            'data' => $awards->map(fn (Award $award): array => $this->awardPayload($award))->values()->all(),
            'meta' => [
                'current_page' => $pagination['page'],
                'per_page' => $pagination['per_page'],
                'total' => $total,
                'from' => $total > 0 ? (($pagination['page'] - 1) * $pagination['per_page']) + 1 : null,
                'to' => $total > 0 ? min($pagination['page'] * $pagination['per_page'], $total) : null,
            ],
        ]);
    }

    /**
     * GET /awards/:id
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $award = $this->findAward($request, $id);
        if ($award === null) {
            return response()->json(['message' => 'Award not found'], 404);
        }

        return response()->json([
            'data' => $this->awardPayload($award),
        ]);
    }

    /**
     * POST /awards
     *
     * Create an award from the winner selected in the workflow.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $validated = Validator::make($request->all(), [
            'rfq_id' => ['required', 'string', 'max:26'],
            'comparison_run_id' => ['required', 'string', 'max:26'],
            'vendor_id' => ['required', 'string', 'max:26'],
        ])->validate();

        $rfq = $this->findRfq($tenantId, $validated['rfq_id']);
        if ($rfq === null) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }

        $comparisonRun = ComparisonRun::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $rfq->id)
            ->where('id', $validated['comparison_run_id'])
            ->first();
        if ($comparisonRun === null) {
            return response()->json(['message' => 'Comparison run not found'], 404);
        }
        if ($comparisonRun->status !== 'final') {
            return response()->json([
                'error' => 'Comparison run is not finalized.',
                'details' => [],
            ], 422);
        }

        $winner = VendorInvitation::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $rfq->id)
            ->where('vendor_id', $validated['vendor_id'])
            ->first();
        if ($winner === null) {
            $winner = QuoteSubmission::query()
                ->where('tenant_id', $tenantId)
                ->where('rfq_id', $rfq->id)
                ->where('vendor_id', $validated['vendor_id'])
                ->first();
        }
        if ($winner === null) {
            return response()->json(['message' => 'Winner vendor not found'], 404);
        }

        $existing = Award::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $rfq->id)
            ->first();
        if ($existing !== null) {
            if (
                (string) $existing->comparison_run_id === (string) $comparisonRun->id
                && (string) $existing->vendor_id === (string) $validated['vendor_id']
            ) {
                return response()->json([
                    'data' => $this->awardPayload($existing),
                ]);
            }

            return response()->json([
                'error' => 'An award already exists for this RFQ.',
                'details' => [],
            ], 409);
        }

        $currency = $this->awardCurrency($rfq);
        $amount = $this->awardAmount($rfq);

        $award = Award::query()->create([
            'tenant_id' => $tenantId,
            'rfq_id' => $rfq->id,
            'comparison_run_id' => $comparisonRun->id,
            'vendor_id' => $validated['vendor_id'],
            'status' => 'signed_off',
            'amount' => $amount,
            'currency' => $currency,
            'split_details' => [],
            'protest_id' => null,
            'signoff_at' => now(),
            'signed_off_by' => $this->userId($request),
        ]);

        return response()->json([
            'data' => $this->awardPayload($award),
        ], 201);
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
        $award = $this->findAward($request, $id);
        if ($award === null) {
            return response()->json(['message' => 'Award not found'], 404);
        }

        if ($award->status !== 'signed_off') {
            $award->status = 'signed_off';
        }
        if ($award->signoff_at === null) {
            $award->signoff_at = now();
        }
        $award->signed_off_by = $this->userId($request);
        $award->save();

        return response()->json([
            'data' => $this->awardPayload($award),
        ]);
    }

    /**
     * @param array<string, string|int> $context
     */
    private function notImplemented(string $operation, array $context = []): JsonResponse
    {
        return response()->json([
            'error' => 'Not implemented',
            'message' => $operation.' is not implemented yet.',
            'context' => $context,
        ], 501);
    }

    private function findAward(Request $request, string $id): ?Award
    {
        $tenantId = $this->tenantId($request);

        return Award::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();
    }

    private function findRfq(string $tenantId, string $rfqId): ?Rfq
    {
        return Rfq::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($builder) use ($rfqId): void {
                $builder->where('id', $rfqId)->orWhere('rfq_number', $rfqId);
            })
            ->first();
    }

    /**
     * @return array{
     *     id: string,
     *     tenant_id: string,
     *     rfq_id: string,
     *     comparison_run_id: ?string,
     *     vendor_id: string,
     *     winner_vendor_id: string,
     *     vendor_name: ?string,
     *     winner_vendor_name: ?string,
     *     status: string,
     *     amount: float,
     *     currency: string,
     *     savings_percentage: float,
     *     signoff_at: ?string,
     *     signed_off_by: ?string,
     *     created_at: ?string,
     *     updated_at: ?string
     * }
     */
    private function awardPayload(Award $award): array
    {
        $vendorName = $this->resolveVendorName((string) $award->tenant_id, (string) $award->rfq_id, (string) $award->vendor_id);
        $rfq = $award->relationLoaded('rfq')
            ? $award->getRelation('rfq')
            : Rfq::query()->where('tenant_id', $award->tenant_id)->where('id', $award->rfq_id)->first();

        return [
            'id' => $award->id,
            'tenant_id' => $award->tenant_id,
            'rfq_id' => $award->rfq_id,
            'comparison_run_id' => $award->comparison_run_id,
            'vendor_id' => $award->vendor_id,
            'winner_vendor_id' => $award->vendor_id,
            'vendor_name' => $vendorName,
            'winner_vendor_name' => $vendorName,
            'status' => $award->status,
            'amount' => (float) $award->amount,
            'currency' => $award->currency,
            'savings_percentage' => (float) ($rfq?->savings_percentage ?? 0),
            'signoff_at' => $award->signoff_at?->toAtomString(),
            'signed_off_by' => $award->signed_off_by,
            'created_at' => $award->created_at?->toAtomString(),
            'updated_at' => $award->updated_at?->toAtomString(),
        ];
    }

    private function awardCurrency(Rfq $rfq): string
    {
        $currency = $rfq->lineItems()
            ->orderBy('sort_order')
            ->value('currency');

        if (! is_string($currency) || $currency === '') {
            return 'USD';
        }

        return $currency;
    }

    private function awardAmount(Rfq $rfq): float
    {
        $estimatedValue = (float) ($rfq->estimated_value ?? 0);
        $savingsPercentage = max(0.0, (float) ($rfq->savings_percentage ?? 0));

        return round($estimatedValue * (1 - ($savingsPercentage / 100)), 2);
    }

    private function resolveVendorName(string $tenantId, string $rfqId, string $vendorId): ?string
    {
        $vendorName = VendorInvitation::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $rfqId)
            ->where('vendor_id', $vendorId)
            ->value('vendor_name');
        if (is_string($vendorName) && $vendorName !== '') {
            return $vendorName;
        }

        $vendorName = QuoteSubmission::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $rfqId)
            ->where('vendor_id', $vendorId)
            ->value('vendor_name');

        return is_string($vendorName) && $vendorName !== '' ? $vendorName : null;
    }
}
