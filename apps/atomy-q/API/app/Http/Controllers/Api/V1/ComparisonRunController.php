<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\ComparisonFinalizeRequest;
use App\Models\ComparisonRun;
use App\Models\QuoteSubmission;
use App\Models\Rfq;
use App\Services\QuoteIntake\ComparisonSnapshotService;
use App\Services\QuoteIntake\DecisionTrailRecorder;
use App\Services\QuoteIntake\QuoteSubmissionReadinessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ComparisonRunController extends Controller
{
    use ExtractsAuthContext;

    public function __construct(
        private readonly ComparisonSnapshotService $snapshotService,
        private readonly QuoteSubmissionReadinessService $readinessService,
        private readonly DecisionTrailRecorder $decisionTrail,
    ) {}

    /**
     * GET /comparison-runs
     * Scoped by tenant_id.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $pagination = $this->paginationParams($request);

        $query = ComparisonRun::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at');

        $rfqFilter = $request->query('rfq_id');
        if (is_string($rfqFilter) && $rfqFilter !== '') {
            $query->where('rfq_id', $rfqFilter);
        }

        $total = $query->count();
        $runs = $query
            ->forPage($pagination['page'], $pagination['per_page'])
            ->get();

        return response()->json([
            'data' => $runs->map(static fn (ComparisonRun $run) => [
                'id' => $run->id,
                'rfq_id' => $run->rfq_id,
                'name' => $run->name,
                'status' => $run->status,
                'is_preview' => (bool) $run->is_preview,
                'created_at' => $run->created_at?->toAtomString(),
            ])->values()->all(),
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
     * GET /comparison-runs/:id
     * Scoped by tenant_id.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        $run = ComparisonRun::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();
        if ($run === null) {
            return response()->json(['message' => 'Comparison run not found'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $run->id,
                'rfq_id' => $run->rfq_id,
                'name' => $run->name,
                'status' => $run->status,
                'is_preview' => (bool) $run->is_preview,
                'snapshot' => $run->response_payload['snapshot'] ?? null,
                'created_at' => $run->created_at?->toAtomString(),
            ],
        ]);
    }

    /**
     * POST /comparison-runs/preview
     * Scoped by tenant_id.
     */
    public function preview(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => 'cr-preview-' . uniqid(),
                'status' => 'preview',
            ],
        ], 201);
    }

    /**
     * POST /comparison-runs/final
     * Method named final_ since final is reserved in PHP.
     * Scoped by tenant_id.
     */
    public function final_(ComparisonFinalizeRequest $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $validated = $request->validated();

        $rfq = Rfq::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $validated['rfq_id'])
            ->withCount('vendorInvitations')
            ->first();
        if ($rfq === null) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }

        $submissions = QuoteSubmission::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $rfq->id)
            ->get();

        if ($submissions->isEmpty()) {
            return response()->json([
                'error' => 'No quote submissions for this RFQ.',
                'details' => [],
            ], 422);
        }

        $vendorsExpected = max(0, (int) ($rfq->vendor_invitations_count ?? 0));
        $minReady = $vendorsExpected >= 2 ? 2 : 1;
        $readyCount = $submissions->filter(static fn (QuoteSubmission $s) => $s->status === 'ready')->count();
        if ($readyCount < $minReady) {
            return response()->json([
                'error' => sprintf('At least %d ready quote submission(s) are required.', $minReady),
                'details' => [],
            ], 422);
        }

        foreach ($submissions as $submission) {
            if ($submission->status !== 'ready') {
                return response()->json([
                    'error' => 'All quote submissions must be in ready state before freezing comparison.',
                    'details' => [],
                ], 422);
            }

            $readiness = $this->readinessService->evaluate($submission);
            if ($readiness['has_blocking_issues']) {
                return response()->json([
                    'error' => 'Blocking normalization issues remain.',
                    'details' => [],
                ], 422);
            }
        }

        $snapshot = $this->snapshotService->freezeForRfq($tenantId, (string) $rfq->id);

        $run = ComparisonRun::query()->create([
            'tenant_id' => $tenantId,
            'rfq_id' => $rfq->id,
            'name' => 'Final comparison',
            'description' => null,
            'idempotency_key' => null,
            'is_preview' => false,
            'created_by' => $this->userId($request),
            'request_payload' => ['rfq_id' => $rfq->id],
            'matrix_payload' => ['rows' => []],
            'scoring_payload' => [],
            'approval_payload' => [],
            'response_payload' => ['snapshot' => $snapshot],
            'readiness_payload' => [
                'all_ready' => true,
                'submission_count' => $submissions->count(),
            ],
            'status' => 'final',
            'version' => 1,
            'expires_at' => null,
            'discarded_at' => null,
            'discarded_by' => null,
        ]);

        $this->decisionTrail->recordSnapshotFrozen(
            $tenantId,
            (string) $rfq->id,
            (string) $run->id,
            [
                'comparison_run_id' => $run->id,
                'quote_submission_count' => $submissions->count(),
                'normalized_line_count' => count($snapshot['normalized_lines']),
            ],
        );

        return response()->json([
            'data' => [
                'id' => $run->id,
                'rfq_id' => $rfq->id,
                'status' => 'final',
                'snapshot' => $snapshot,
            ],
        ], 201);
    }

    /**
     * GET /comparison-runs/:id/matrix
     * Scoped by tenant_id.
     */
    public function matrix(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'matrix' => [],
                'headers' => [],
            ],
        ]);
    }

    /**
     * GET /comparison-runs/:id/readiness
     * Scoped by tenant_id.
     */
    public function readiness(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'ready' => false,
                'issues' => [],
            ],
        ]);
    }

    /**
     * PATCH /comparison-runs/:id/scoring-model
     * Scoped by tenant_id.
     */
    public function updateScoringModel(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'scoring_model' => [],
            ],
        ]);
    }

    /**
     * POST /comparison-runs/:id/lock
     * Scoped by tenant_id.
     */
    public function lock(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'locked' => true,
            ],
        ]);
    }

    /**
     * POST /comparison-runs/:id/unlock
     * Scoped by tenant_id.
     */
    public function unlock(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'locked' => false,
            ],
        ]);
    }
}
