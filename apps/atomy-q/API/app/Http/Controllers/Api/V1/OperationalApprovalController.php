<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\ApprovalOperations\DTOs\OperationalApprovalDecision;
use Nexus\ApprovalOperations\DTOs\RecordApprovalDecisionCommand;
use Nexus\ApprovalOperations\DTOs\StartOperationalApprovalCommand;
use Nexus\ApprovalOperations\DTOs\ApprovalSubjectRef;
use Nexus\ApprovalOperations\Services\ApprovalProcessCoordinator;
use Nexus\ApprovalOperations\Services\ApprovalSlaViewBuilder;

final class OperationalApprovalController extends Controller
{
    public function __construct(
        private readonly ApprovalProcessCoordinator $coordinator,
        private readonly ApprovalSlaViewBuilder $slaViewBuilder,
    ) {
    }

    private function tenantId(Request $request): string
    {
        $id = (string) $request->attributes->get('auth_tenant_id', '');
        if ($id === '') {
            abort(403, 'Tenant context required');
        }

        return $id;
    }

    /**
     * Start a generic operational approval (distinct from RFQ quote approvals).
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $validated = $request->validate([
            'subject_type' => 'required|string|max:128',
            'subject_id' => 'required|string|max:256',
            'context' => 'sometimes|array',
        ]);

        $principalId = (string) $request->attributes->get('auth_user_id', '');
        if ($principalId === '') {
            abort(401, 'User context required');
        }

        $command = new StartOperationalApprovalCommand(
            tenantId: $tenantId,
            subject: new ApprovalSubjectRef(
                subjectType: $validated['subject_type'],
                subjectId: $validated['subject_id'],
            ),
            initiatorPrincipalId: $principalId,
            context: $validated['context'] ?? [],
        );

        $result = $this->coordinator->start($command);

        return response()->json([
            'data' => [
                'instance_id' => $result->instanceId,
                'workflow_instance_id' => $result->workflowInstanceId,
            ],
        ], 201);
    }

    public function show(Request $request, string $instanceId): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $sla = $this->slaViewBuilder->build($tenantId, $instanceId);

        return response()->json([
            'data' => [
                'instance_id' => $instanceId,
                'due_at' => $sla->dueAtIso8601,
                'seconds_remaining' => $sla->secondsRemaining,
            ],
        ]);
    }

    public function storeDecision(Request $request, string $instanceId): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $validated = $request->validate([
            'decision' => 'required|string|in:approve,reject',
            'comment' => 'sometimes|nullable|string|max:8000',
        ]);

        $principalId = (string) $request->attributes->get('auth_user_id', '');
        if ($principalId === '') {
            abort(401, 'User context required');
        }

        $decision = $validated['decision'] === 'approve'
            ? OperationalApprovalDecision::Approve
            : OperationalApprovalDecision::Reject;

        $this->coordinator->recordDecision(new RecordApprovalDecisionCommand(
            tenantId: $tenantId,
            instanceId: $instanceId,
            actorPrincipalId: $principalId,
            decision: $decision,
            comment: $validated['comment'] ?? null,
        ));

        return response()->json(['data' => ['ok' => true]], 200);
    }
}
