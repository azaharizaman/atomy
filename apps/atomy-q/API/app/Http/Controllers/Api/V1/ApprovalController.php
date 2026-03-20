<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use App\Models\Approval;
use App\Models\ComparisonRun;
use App\Models\QuoteSubmission;
use App\Services\QuoteIntake\QuoteSubmissionReadinessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ApprovalController extends Controller
{
    use ExtractsAuthContext;

    public function __construct(
        private readonly QuoteSubmissionReadinessService $readinessService,
    ) {}

    /**
     * GET /approvals
     *
     * Query: type, status, priority, page, per_page
     */
    public function index(Request $request): JsonResponse
    {
        $params = $this->paginationParams($request);

        return response()->json([
            'data' => [],
            'meta' => [
                'current_page' => $params['page'],
                'per_page' => $params['per_page'],
                'total' => 0,
            ],
        ]);
    }

    /**
     * GET /approvals/:id
     */
    public function show(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'type' => 'quote_approval',
                'status' => 'pending',
                'priority' => 'normal',
                'created_at' => null,
            ],
        ]);
    }

    /**
     * POST /approvals/:id/approve
     *
     * Body: reason (required)
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        $approval = Approval::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();
        if ($approval === null) {
            return response()->json(['message' => 'Approval not found'], 404);
        }

        if ($approval->comparison_run_id === null) {
            return response()->json([
                'error' => 'Approval is not linked to a frozen comparison run.',
                'details' => [],
            ], 422);
        }

        $run = ComparisonRun::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $approval->comparison_run_id)
            ->first();
        if ($run === null || $run->status !== 'final') {
            return response()->json([
                'error' => 'Comparison run is not finalized.',
                'details' => [],
            ], 422);
        }

        $snapshot = $run->response_payload['snapshot'] ?? null;
        if (! is_array($snapshot) || ! isset($snapshot['normalized_lines']) || ! is_array($snapshot['normalized_lines'])) {
            return response()->json([
                'error' => 'Comparison snapshot is missing.',
                'details' => [],
            ], 422);
        }

        $submissions = QuoteSubmission::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $approval->rfq_id)
            ->get();

        foreach ($submissions as $submission) {
            if ($submission->status !== 'ready') {
                return response()->json([
                    'error' => 'All quote submissions must be ready before approval.',
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

        $approval->status = 'approved';
        $approval->approved_at = now();
        $approval->approved_by = $this->userId($request);
        $approval->save();

        return response()->json([
            'data' => [
                'id' => $id,
                'status' => 'approved',
                'approved_at' => $approval->approved_at?->toAtomString(),
            ],
        ]);
    }

    /**
     * POST /approvals/:id/reject
     *
     * Body: reason (required)
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'status' => 'rejected',
                'rejected_at' => null,
            ],
        ]);
    }

    /**
     * POST /approvals/:id/return
     *
     * Body: reason, instructions
     */
    public function return_(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'status' => 'returned',
                'returned_at' => null,
            ],
        ]);
    }

    /**
     * POST /approvals/:id/reassign
     *
     * Body: assignee_id
     */
    public function reassign(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'assignee_id' => 'stub-assignee-id',
                'reassigned_at' => null,
            ],
        ]);
    }

    /**
     * POST /approvals/:id/snooze
     *
     * Body: duration_hours
     */
    public function snooze(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'snoozed_until' => null,
            ],
        ]);
    }

    /**
     * POST /approvals/:id/request-evidence
     */
    public function requestEvidence(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'evidence_requested_at' => null,
            ],
        ]);
    }

    /**
     * POST /approvals/bulk-approve
     *
     * Body: ids[], reason
     */
    public function bulkApprove(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'affected' => 0,
                'ids' => [],
            ],
        ]);
    }

    /**
     * POST /approvals/bulk-reject
     *
     * Body: ids[], reason
     */
    public function bulkReject(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'affected' => 0,
                'ids' => [],
            ],
        ]);
    }

    /**
     * POST /approvals/bulk-reassign
     *
     * Body: ids[], assignee_id
     */
    public function bulkReassign(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'affected' => 0,
                'ids' => [],
            ],
        ]);
    }

    /**
     * GET /approvals/:id/history
     */
    public function history(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [],
        ]);
    }
}
