<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ApprovalController extends Controller
{
    use ExtractsAuthContext;

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
        return response()->json([
            'data' => [
                'id' => $id,
                'status' => 'approved',
                'approved_at' => null,
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
