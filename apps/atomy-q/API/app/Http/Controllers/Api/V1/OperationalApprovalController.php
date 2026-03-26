<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Nexus\ApprovalOperations\Contracts\ApprovalInstanceQueryInterface;
use Nexus\ApprovalOperations\DTOs\OperationalApprovalDecision;
use Nexus\ApprovalOperations\DTOs\RecordApprovalDecisionCommand;
use Nexus\ApprovalOperations\DTOs\StartOperationalApprovalCommand;
use Nexus\ApprovalOperations\DTOs\ApprovalSubjectRef;
use Nexus\ApprovalOperations\DTOs\StartedOperationalApprovalResult;
use Nexus\ApprovalOperations\Services\ApprovalProcessCoordinator;
use Nexus\ApprovalOperations\Services\ApprovalSlaViewBuilder;

final class OperationalApprovalController extends Controller
{
    public function __construct(
        private readonly ApprovalProcessCoordinator $coordinator,
        private readonly ApprovalSlaViewBuilder $slaViewBuilder,
        private readonly ApprovalInstanceQueryInterface $instancesQuery,
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

        $result = DB::transaction(fn (): StartedOperationalApprovalResult => $this->coordinator->start($command));

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

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $validated = $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);
        $page = (int) ($validated['page'] ?? 1);
        $perPage = (int) ($validated['per_page'] ?? 25);

        $pageResult = $this->instancesQuery->findByTenant($tenantId, $page, $perPage);
        $items = array_map(
            function ($instance): array {
                $sla = $this->slaViewBuilder->buildFromInstance($instance);

                return [
                    'instance_id' => $instance->id,
                    'tenant_id' => $instance->tenantId,
                    'subject_type' => $instance->subject->subjectType,
                    'subject_id' => $instance->subject->subjectId,
                    'status' => $instance->status->value,
                    'due_at' => $sla->dueAtIso8601,
                    'seconds_remaining' => $sla->secondsRemaining,
                ];
            },
            $pageResult->items,
        );

        return response()->json([
            'data' => $items,
            'meta' => [
                'total' => $pageResult->total,
                'per_page' => $pageResult->perPage,
                'current_page' => $pageResult->currentPage,
                'last_page' => $pageResult->lastPage,
            ],
        ]);
    }

    public function storeDecision(Request $request, string $instanceId): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $validated = $request->validate([
            'decision' => 'required|string|in:approve,reject',
            'comment' => 'sometimes|nullable|string|max:8000',
            'attachment_storage_key' => 'sometimes|nullable|string|max:512',
        ]);

        $principalId = (string) $request->attributes->get('auth_user_id', '');
        if ($principalId === '') {
            abort(401, 'User context required');
        }

        $decision = $validated['decision'] === 'approve'
            ? OperationalApprovalDecision::Approve
            : OperationalApprovalDecision::Reject;

        DB::transaction(function () use ($tenantId, $instanceId, $principalId, $decision, $validated): void {
            $this->coordinator->recordDecision(new RecordApprovalDecisionCommand(
                tenantId: $tenantId,
                instanceId: $instanceId,
                actorPrincipalId: $principalId,
                decision: $decision,
                comment: $validated['comment'] ?? null,
                attachmentStorageKey: $validated['attachment_storage_key'] ?? null,
            ));
        });

        return response()->json(['data' => ['ok' => true]], 200);
    }
}
