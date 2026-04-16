<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use App\Models\Award;
use App\Models\Debrief;
use App\Models\ComparisonRun;
use App\Models\QuoteSubmission;
use App\Models\Rfq;
use App\Services\QuoteIntake\DecisionTrailRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    public function store(Request $request, DecisionTrailRecorder $decisionTrail): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $validated = $request->validate([
            'rfq_id' => ['required', 'string'],
            'comparison_run_id' => ['required', 'string'],
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

        $submission = QuoteSubmission::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $rfq->id)
            ->where('vendor_id', $validated['vendor_id'])
            ->first();

        if ($submission === null) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        $run = ComparisonRun::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $validated['comparison_run_id'])
            ->where('rfq_id', $rfq->id)
            ->first();
        if ($run === null) {
            return response()->json(['message' => 'Comparison run not found'], 404);
        }

        if (! in_array($run->status, ['frozen', 'final', 'completed'], true)) {
            return response()->json(['message' => 'Comparison run is not finalized for award creation'], 422);
        }

        /** @var Award $award */
        $award = DB::transaction(function () use ($tenantId, $rfq, $validated, $decisionTrail): Award {
            /** @var Award $award */
            $award = Award::query()->create([
                'tenant_id' => $tenantId,
                'rfq_id' => $rfq->id,
                'comparison_run_id' => $validated['comparison_run_id'],
                'vendor_id' => $validated['vendor_id'],
                'status' => 'pending',
                'amount' => $validated['amount'],
                'currency' => strtoupper((string) $validated['currency']),
                'split_details' => $validated['split_details'] ?? null,
                'protest_id' => null,
                'signoff_at' => null,
                'signed_off_by' => null,
            ]);

            $decisionTrail->recordAwardCreated(
                $tenantId,
                $award->rfq_id,
                $award->comparison_run_id,
                [
                    'award_id' => $award->id,
                    'vendor_id' => $award->vendor_id,
                    'status' => $award->status,
                ],
            );

            return $award;
        });

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
    public function debrief(Request $request, string $id, string $vendorId, DecisionTrailRecorder $decisionTrail): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $award = $this->findAward($tenantId, $id);
        if ($award === null) {
            return response()->json(['message' => 'Award not found'], 404);
        }

        $submission = QuoteSubmission::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $award->rfq_id)
            ->where('vendor_id', $vendorId)
            ->first();

        if ($submission === null) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        /** @var Debrief $debrief */
        $debrief = DB::transaction(function () use ($tenantId, $award, $vendorId, $validated, $decisionTrail): Debrief {
            /** @var Debrief $debrief */
            $debrief = Debrief::query()->firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'award_id' => $award->id,
                    'vendor_id' => $vendorId,
                ],
                [
                    'rfq_id' => $award->rfq_id,
                    'message' => $validated['message'] ?? null,
                    'debriefed_at' => now(),
                ],
            );

            if ($debrief->wasRecentlyCreated && $award->comparison_run_id !== null) {
                $decisionTrail->recordAwardDebriefed(
                    $tenantId,
                    $award->rfq_id,
                    $award->comparison_run_id,
                    [
                        'award_id' => $award->id,
                        'vendor_id' => $vendorId,
                        'message_present' => array_key_exists('message', $validated),
                    ],
                );
            }

            return $debrief;
        });

        return response()->json([
            'data' => [
                'award_id' => $debrief->award_id,
                'vendor_id' => $debrief->vendor_id,
                'vendor_name' => $submission->vendor_name,
                'message' => $debrief->message,
                'status' => $award->status,
                'debriefed_at' => $debrief->debriefed_at?->toAtomString(),
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
    public function signoff(Request $request, string $id, DecisionTrailRecorder $decisionTrail): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        $award = $this->findAward($tenantId, $id);
        if ($award === null) {
            return response()->json(['message' => 'Award not found'], 404);
        }

        if ($award->status === 'signed_off' && $award->signoff_at !== null) {
            return response()->json([
                'data' => $this->serializeAward($award),
            ]);
        }

        /** @var Award|null $award */
        $award = DB::transaction(function () use ($tenantId, $request, $id, $decisionTrail): ?Award {
            $award = Award::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            if ($award === null) {
                return null;
            }

            if ($award->status === 'signed_off' && $award->signoff_at !== null) {
                return $award;
            }

            $award->status = 'signed_off';
            $award->signoff_at = now();
            $award->signed_off_by = $this->userId($request);
            $award->save();

            if ($award->comparison_run_id !== null) {
                $decisionTrail->recordAwardSignedOff(
                    $tenantId,
                    $award->rfq_id,
                    $award->comparison_run_id,
                    [
                        'award_id' => $award->id,
                        'signed_off_by' => $award->signed_off_by,
                        'status' => $award->status,
                    ],
                );
            }

            return $award;
        });

        if ($award === null) {
            return response()->json(['message' => 'Award not found'], 404);
        }

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
        $comparisonRun = $award->comparisonRun()->select('id', 'response_payload')->first();
        $snapshot = $comparisonRun !== null ? ($comparisonRun->response_payload['snapshot'] ?? null) : null;
        $vendors = [];
        if (is_array($snapshot) && array_key_exists('vendors', $snapshot) && is_array($snapshot['vendors'])) {
            $vendors = array_values(array_map(static function (array $vendor): array {
                return [
                    'vendor_id' => isset($vendor['vendor_id']) ? (string) $vendor['vendor_id'] : '',
                    'vendor_name' => array_key_exists('vendor_name', $vendor) && $vendor['vendor_name'] !== null ? (string) $vendor['vendor_name'] : null,
                    'quote_submission_id' => array_key_exists('quote_submission_id', $vendor) && $vendor['quote_submission_id'] !== null ? (string) $vendor['quote_submission_id'] : null,
                ];
            }, $snapshot['vendors']));
        }

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
            'comparison' => [
                'vendors' => $vendors,
            ],
        ];
    }
}
