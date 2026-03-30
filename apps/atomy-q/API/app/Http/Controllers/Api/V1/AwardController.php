<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use App\Models\Award;
use App\Models\ComparisonRun;
use App\Models\QuoteSubmission;
use App\Models\Rfq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

        $rfqId = $request->query('rfqId', $request->query('rfq_id'));
        if (is_string($rfqId) && $rfqId !== '') {
            $query->where('rfq_id', $rfqId);
        }

        $paginator = $query->paginate($pagination['per_page'], ['*'], 'page', $pagination['page']);

        return response()->json([
            'data' => $paginator->getCollection()
                ->map(fn (Award $award): array => $this->serializeAward($award))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'total_pages' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * POST /awards
     *
     * Create an award. Returns 201.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $validated = $request->validate([
            'rfq_id' => ['required', 'string'],
            'comparison_run_id' => ['nullable', 'string'],
            'vendor_id' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'split_details' => ['nullable', 'array'],
        ]);

        $rfq = Rfq::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $validated['rfq_id'])
            ->first();

        if ($rfq === null) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }

        if (! empty($validated['comparison_run_id'])) {
            $run = ComparisonRun::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $validated['comparison_run_id'])
                ->where('rfq_id', $rfq->id)
                ->first();
            if ($run === null) {
                return response()->json(['message' => 'Comparison run not found'], 404);
            }
        }

        $award = Award::query()->create([
            'tenant_id' => $tenantId,
            'rfq_id' => $rfq->id,
            'comparison_run_id' => $validated['comparison_run_id'] ?? null,
            'vendor_id' => $validated['vendor_id'],
            'status' => 'pending',
            'amount' => $validated['amount'],
            'currency' => strtoupper((string) $validated['currency']),
            'split_details' => $validated['split_details'] ?? null,
            'protest_id' => null,
            'signoff_at' => null,
            'signed_off_by' => null,
        ]);

        return response()->json([
            'data' => $this->serializeAward($award),
        ], 201);
    }

    /**
     * PUT /awards/:id/split
     */
    public function updateSplit(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $validated = $request->validate([
            'split_details' => ['required', 'array'],
        ]);

        $award = $this->findAward($tenantId, $id);
        if ($award === null) {
            return response()->json(['message' => 'Award not found'], 404);
        }

        $award->split_details = $validated['split_details'];
        $award->save();

        return response()->json([
            'data' => $this->serializeAward($award),
        ]);
    }

    /**
     * POST /awards/:id/debrief/:vendorId
     */
    public function debrief(Request $request, string $id, string $vendorId): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $award = $this->findAward($tenantId, $id);
        if ($award === null || $award->vendor_id !== $vendorId) {
            return response()->json(['message' => 'Award not found'], 404);
        }

        return response()->json([
            'data' => [
                'award_id' => $award->id,
                'vendor_id' => $vendorId,
                'message' => $validated['message'] ?? null,
                'status' => $award->status,
                'debriefed_at' => now()->toAtomString(),
            ],
        ]);
    }

    /**
     * POST /awards/:id/protest
     */
    public function protest(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $award = $this->findAward($tenantId, $id);
        if ($award === null) {
            return response()->json(['message' => 'Award not found'], 404);
        }

        $award->status = 'protested';
        $award->protest_id = (string) Str::ulid();
        $award->save();

        return response()->json([
            'data' => $this->serializeAward($award) + [
                'reason' => $validated['reason'] ?? null,
            ],
        ]);
    }

    /**
     * PATCH /awards/:id/protest/:protestId/resolve
     */
    public function resolveProtest(Request $request, string $id, string $protestId): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        $award = $this->findAward($tenantId, $id);
        if ($award === null || $award->protest_id === null || $award->protest_id !== $protestId) {
            return response()->json(['message' => 'Award protest not found'], 404);
        }

        $award->protest_id = null;
        $award->status = $award->signoff_at === null ? 'pending' : 'signed_off';
        $award->save();

        return response()->json([
            'data' => $this->serializeAward($award),
        ]);
    }

    /**
     * POST /awards/:id/signoff
     */
    public function signoff(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        $award = $this->findAward($tenantId, $id);
        if ($award === null) {
            return response()->json(['message' => 'Award not found'], 404);
        }

        $award->status = 'signed_off';
        $award->signoff_at = now();
        $award->signed_off_by = $this->userId($request);
        $award->save();

        return response()->json([
            'data' => $this->serializeAward($award),
        ]);
    }

    private function findAward(string $tenantId, string $id): ?Award
    {
        return Award::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAward(Award $award): array
    {
        $submission = QuoteSubmission::query()
            ->where('tenant_id', $award->tenant_id)
            ->where('rfq_id', $award->rfq_id)
            ->where('vendor_id', $award->vendor_id)
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->first();

        $rfq = $award->rfq()->select('id', 'title', 'rfq_number')->first();

        return [
            'id' => $award->id,
            'rfq_id' => $award->rfq_id,
            'rfq_title' => $rfq?->title,
            'rfq_number' => $rfq?->rfq_number,
            'comparison_run_id' => $award->comparison_run_id,
            'vendor_id' => $award->vendor_id,
            'vendor_name' => $submission?->vendor_name,
            'status' => $award->status,
            'amount' => $award->amount !== null ? (string) $award->amount : null,
            'currency' => $award->currency,
            'split_details' => $award->split_details ?? [],
            'protest_id' => $award->protest_id,
            'signoff_at' => $award->signoff_at?->toAtomString(),
            'signed_off_by' => $award->signed_off_by,
        ];
    }
}
