<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Throwable;
use App\Adapters\Ai\Contracts\ComparisonAwardAiClientInterface;
use App\Adapters\Ai\Contracts\AiRuntimeStatusInterface;
use App\Adapters\Ai\DTOs\ApprovalSummaryRequest;
use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Api\V1\Concerns\InteractsWithAiAvailability;
use App\Http\Controllers\Controller;
use App\Models\Approval;
use App\Models\ComparisonRun;
use App\Models\QuoteSubmission;
use App\Services\QuoteIntake\DecisionTrailRecorder;
use App\Services\QuoteIntake\QuoteSubmissionReadinessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Nexus\Common\Contracts\ClockInterface;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;

final class ApprovalController extends Controller
{
    use ExtractsAuthContext;
    use InteractsWithAiAvailability;

    public function __construct(
        private readonly QuoteSubmissionReadinessService $readinessService,
        private readonly ComparisonAwardAiClientInterface $comparisonAwardAiClient,
        private readonly DecisionTrailRecorder $decisionTrailRecorder,
        private readonly AiRuntimeStatusInterface $aiRuntimeStatus,
        private readonly ClockInterface $clock,
    ) {}

    /**
     * GET /approvals
     *
     * Query: type, status, priority, page, per_page
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $params = $this->paginationParams($request);

        $query = Approval::query()
            ->where('tenant_id', $tenantId)
            ->with([
                'rfq' => static function ($relation) use ($tenantId): void {
                    $relation
                        ->where('tenant_id', $tenantId)
                        ->select('id', 'title', 'tenant_id');
                },
                'requestedBy' => static function ($relation) use ($tenantId): void {
                    $relation
                        ->where('tenant_id', $tenantId)
                        ->select('id', 'name', 'email', 'tenant_id');
                },
            ])
            ->orderByDesc('requested_at')
            ->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $query->where('status', (string) $status);
        }

        if ($type = $request->query('type')) {
            $query->where('type', (string) $type);
        }

        if ($priority = $request->query('priority')) {
            $p = strtolower((string) $priority);
            if ($p === 'high') {
                $query->where('level', '>=', 3);
            } elseif ($p === 'medium') {
                $query->where('level', 2);
            } elseif ($p === 'low') {
                $query->where('level', 1);
            }
        }

        if ($rfqId = $request->query('rfq_id')) {
            $query->where('rfq_id', (string) $rfqId);
        }

        $paginator = $query->paginate($params['per_page'], ['*'], 'page', $params['page']);

        $rows = $paginator->getCollection()->map(function (Approval $approval): array {
            return $this->serializeApprovalListRow($approval);
        })->values();

        return response()->json([
            'data' => $rows,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'total_pages' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * GET /approvals/:id
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $approval = $this->loadApprovalWithRelations($this->tenantId($request), $id);

        if ($approval === null) {
            return response()->json(['message' => 'Approval not found'], 404);
        }

        return response()->json([
            'data' => $this->serializeApprovalDetail($approval),
        ]);
    }

    /**
     * GET /approvals/:id/summary
     * Scoped by tenant_id.
     */
    public function summary(Request $request, string $id): JsonResponse
    {
        $approval = $this->loadApprovalWithRelations($this->tenantId($request), $id);
        if ($approval === null) {
            return response()->json(['message' => 'Approval not found'], 404);
        }

        $comparisonRun = $approval->comparisonRun;
        if (! $comparisonRun instanceof ComparisonRun) {
            return response()->json(['message' => 'Comparison run not found'], 404);
        }

        $storedSummary = $this->storedApprovalSummary($comparisonRun, (string) $approval->id);
        if ($storedSummary === null) {
            return response()->json(['message' => 'Approval summary not found'], 404);
        }

        return response()->json([
            'data' => [
                'ai_summary' => $storedSummary,
            ],
        ]);
    }

    /**
     * POST /approvals/:id/summary/generate
     * Scoped by tenant_id.
     */
    public function generateSummary(Request $request, string $id): JsonResponse
    {
        $approval = $this->loadApprovalWithRelations($this->tenantId($request), $id);
        if ($approval === null) {
            return response()->json(['message' => 'Approval not found'], 404);
        }

        $comparisonRun = $approval->comparisonRun;
        if (! $comparisonRun instanceof ComparisonRun) {
            return response()->json(['message' => 'Comparison run not found'], 404);
        }

        $storedSummary = $this->storedApprovalSummary($comparisonRun, (string) $approval->id);
        if ($storedSummary !== null) {
            return response()->json([
                'data' => [
                    'ai_summary' => $storedSummary,
                ],
            ]);
        }

        $artifact = $this->buildApprovalSummaryEnvelope($approval, $comparisonRun);
        if (($artifact['available'] ?? false) === true) {
            $this->persistApprovalSummaryArtifact($comparisonRun, $approval, $artifact);
        }

        return response()->json([
            'data' => [
                'ai_summary' => $artifact,
            ],
        ], ($artifact['available'] ?? false) === true ? 200 : 503);
    }

    /**
     * POST /approvals/:id/approve
     *
     * Body: reason (required)
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

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
        if (array_key_exists('reason', $validated) && $validated['reason'] !== null) {
            $approval->notes = (string) $validated['reason'];
        }
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
        $tenantId = $this->tenantId($request);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        /** @var Approval|null $approval */
        $approval = Approval::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        if ($approval === null) {
            return response()->json(['message' => 'Approval not found'], 404);
        }

        if ($approval->status !== 'pending') {
            return response()->json([
                'error' => 'Approval is not pending.',
                'details' => [],
            ], 422);
        }

        $approval->status = 'rejected';
        $approval->notes = isset($validated['reason']) ? (string) $validated['reason'] : $approval->notes;
        $approval->approved_at = now();
        $approval->approved_by = $this->userId($request);
        $approval->save();

        return response()->json([
            'data' => [
                'id' => $id,
                'status' => 'rejected',
                'rejected_at' => $approval->approved_at?->toAtomString(),
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

    /**
     * @return array<string, mixed>
     */
    private function serializeApprovalListRow(Approval $approval): array
    {
        $level = (int) ($approval->level ?? 1);
        $priority = $level >= 3 ? 'high' : ($level >= 2 ? 'medium' : 'low');
        $rfqTitle = $approval->rfq?->title ?? 'Requisition';
        $typeLabel = ucwords(str_replace('_', ' ', (string) $approval->type));

        return [
            'id' => $approval->id,
            'rfq_id' => $approval->rfq_id,
            'rfq_title' => $rfqTitle,
            'type' => $approval->type,
            'type_label' => $typeLabel,
            'status' => $approval->status,
            'priority' => $priority,
            'summary' => $typeLabel . ' — ' . $rfqTitle,
            'sla' => '—',
            'sla_variant' => 'safe',
            'assignee' => $approval->requestedBy?->name ?? $approval->requestedBy?->email ?? '—',
            'requested_at' => $approval->requested_at?->toAtomString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeApprovalDetail(Approval $approval): array
    {
        $run = $approval->comparisonRun;
        $row = $this->serializeApprovalListRow($approval);
        $row['rfq_number'] = $approval->rfq?->rfq_number;
        $row['notes'] = $approval->notes;
        $row['comparison_run'] = $run !== null ? [
            'id' => $run->id,
            'name' => $run->name,
            'status' => $run->status,
            'is_preview' => (bool) ($run->is_preview ?? false),
        ] : null;
        $row['created_at'] = $approval->created_at?->toAtomString();

        return $row;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed>|null $provenance
     * @return array<string, mixed>
     */
    private function artifactEnvelope(
        string $featureKey,
        string $capabilityGroup,
        array $payload,
        ?array $provenance = null,
    ): array {
        return [
            'feature_key' => $featureKey,
            'capability_group' => $capabilityGroup,
            'available' => true,
            'status' => AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
            'manual_continuity' => 'available',
            'payload' => $payload,
            'provenance' => $provenance,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function unavailableArtifactEnvelope(string $featureKey, string $capabilityGroup): array
    {
        $capabilityStatus = $this->aiCapabilityStatus($featureKey);
        $statusSnapshot = $this->aiStatusSnapshot();

        return [
            'feature_key' => $featureKey,
            'capability_group' => $capabilityGroup,
            'available' => false,
            'status' => $capabilityStatus?->status ?? AiStatusSchema::CAPABILITY_STATUS_UNAVAILABLE,
            'manual_continuity' => 'available',
            'fallback_ui_mode' => $capabilityStatus?->fallbackUiMode ?? AiStatusSchema::FALLBACK_UI_MODE_SHOW_UNAVAILABLE_MESSAGE,
            'message_key' => $capabilityStatus?->messageKey ?? 'ai.capability.unavailable',
            'reason_codes' => $capabilityStatus?->reasonCodes ?? $statusSnapshot->reasonCodes,
            'diagnostics' => $capabilityStatus?->diagnostics ?? ['mode' => $statusSnapshot->mode],
            'payload' => null,
            'provenance' => [
                'source' => 'deterministic',
                'endpoint_group' => AiStatusSchema::ENDPOINT_GROUP_COMPARISON_AWARD,
                'provider_name' => $this->providerName(),
                'generated_at' => $this->clock->now()->format(DATE_ATOM),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function providerArtifactProvenance(array $payload, string $endpointGroup): array
    {
        $provenance = is_array($payload['provenance'] ?? null) ? $payload['provenance'] : [];

        return array_replace($provenance, [
            'source' => 'provider',
            'provider_name' => $this->providerName(),
            'endpoint_group' => $endpointGroup,
            'generated_at' => $provenance['generated_at'] ?? $this->clock->now()->format(DATE_ATOM),
        ]);
    }

    private function providerName(): ?string
    {
        try {
            $providerName = $this->aiRuntimeStatus->providerName();
        } catch (Throwable) {
            return null;
        }

        return is_string($providerName) && trim($providerName) !== '' ? trim($providerName) : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeComparisonContext(ComparisonRun $comparisonRun): array
    {
        $responsePayload = is_array($comparisonRun->response_payload) ? $comparisonRun->response_payload : [];

        return [
            'comparison_run' => [
                'id' => (string) $comparisonRun->id,
                'status' => (string) $comparisonRun->status,
                'is_preview' => (bool) ($comparisonRun->is_preview ?? false),
                'version' => (int) ($comparisonRun->version ?? 1),
            ],
            'snapshot' => is_array($responsePayload['snapshot'] ?? null) ? $responsePayload['snapshot'] : null,
            'matrix' => is_array($comparisonRun->matrix_payload) ? $comparisonRun->matrix_payload : null,
            'readiness' => is_array($comparisonRun->readiness_payload) ? $comparisonRun->readiness_payload : null,
            'approval' => is_array($comparisonRun->approval_payload) ? $comparisonRun->approval_payload : null,
            'ai_overlay' => is_array($responsePayload['ai_overlay'] ?? null) ? $responsePayload['ai_overlay'] : null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function storedApprovalSummary(ComparisonRun $comparisonRun, string $approvalId): ?array
    {
        $responsePayload = $comparisonRun->response_payload ?? [];
        if (! is_array($responsePayload)) {
            return null;
        }

        $artifact = $responsePayload['ai_artifacts']['approval_summary'][$approvalId] ?? null;

        return is_array($artifact) ? $artifact : null;
    }

    /**
     * @param array<string, mixed> $artifact
     */
    private function persistApprovalSummaryArtifact(ComparisonRun $comparisonRun, Approval $approval, array $artifact): void
    {
        DB::transaction(function () use ($comparisonRun, $approval, $artifact): void {
            /** @var ComparisonRun $lockedRun */
            $lockedRun = ComparisonRun::query()
                ->where('id', $comparisonRun->id)
                ->lockForUpdate()
                ->firstOrFail();

            $responsePayload = $lockedRun->response_payload ?? [];
            if (! is_array($responsePayload)) {
                $responsePayload = [];
            }

            $artifacts = $responsePayload['ai_artifacts'] ?? [];
            if (! is_array($artifacts)) {
                $artifacts = [];
            }

            $summaryArtifacts = $artifacts['approval_summary'] ?? [];
            if (! is_array($summaryArtifacts)) {
                $summaryArtifacts = [];
            }

            $summaryArtifacts[(string) $approval->id] = $artifact;
            $artifacts['approval_summary'] = $summaryArtifacts;
            $responsePayload['ai_artifacts'] = $artifacts;
            $lockedRun->response_payload = $responsePayload;
            $lockedRun->save();

            $this->decisionTrailRecorder->recordAiArtifactGenerated(
                tenantId: (string) $approval->tenant_id,
                rfqId: (string) $approval->rfq_id,
                comparisonRunId: (string) $approval->comparison_run_id,
                eventType: 'approval_ai_summary_generated:' . (string) $approval->id,
                summary: [
                    'artifact_kind' => 'approval_ai_summary',
                    'artifact_origin' => 'provider_drafted',
                    'feature_key' => 'approval_ai_summary',
                    'approval_id' => (string) $approval->id,
                    'artifact' => $artifact,
                    'provenance' => is_array($artifact['provenance'] ?? null) ? $artifact['provenance'] : [],
                ],
            );
        });
    }

    private function loadApprovalWithRelations(string $tenantId, string $id): ?Approval
    {
        /** @var Approval|null $approval */
        $approval = Approval::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->with([
                'rfq' => static function ($relation) use ($tenantId): void {
                    $relation
                        ->where('tenant_id', $tenantId)
                        ->select('id', 'title', 'tenant_id', 'rfq_number');
                },
                'comparisonRun' => static function ($relation) use ($tenantId): void {
                    $relation
                        ->where('tenant_id', $tenantId)
                        ->select(
                            'id',
                            'name',
                            'status',
                            'is_preview',
                            'tenant_id',
                            'response_payload',
                            'matrix_payload',
                            'readiness_payload',
                            'approval_payload',
                            'version',
                        );
                },
                'requestedBy' => static function ($relation) use ($tenantId): void {
                    $relation
                        ->where('tenant_id', $tenantId)
                        ->select('id', 'name', 'email', 'tenant_id');
                },
                'approvedByUser' => static function ($relation) use ($tenantId): void {
                    $relation
                        ->where('tenant_id', $tenantId)
                        ->select('id', 'name', 'email', 'tenant_id');
                },
            ])
            ->first();

        return $approval;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildApprovalSummaryEnvelope(Approval $approval, ComparisonRun $comparisonRun): array
    {
        if (! $this->aiCapabilityAvailable('approval_ai_summary')) {
            return $this->unavailableArtifactEnvelope(
                featureKey: 'approval_ai_summary',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_GOVERNANCE_INTELLIGENCE,
            );
        }

        try {
            $summary = $this->comparisonAwardAiClient->approvalSummary(new ApprovalSummaryRequest(
                tenantId: (string) $approval->tenant_id,
                approvalId: (string) $approval->id,
                rfqId: (string) $approval->rfq_id,
                comparisonRunId: (string) $approval->comparison_run_id,
                approval: $this->serializeApprovalDetail($approval),
                comparisonContext: $this->serializeComparisonContext($comparisonRun),
            ))->payload;
        } catch (Throwable $exception) {
            report($exception);

            return $this->unavailableArtifactEnvelope(
                featureKey: 'approval_ai_summary',
                capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_GOVERNANCE_INTELLIGENCE,
            );
        }

        return $this->artifactEnvelope(
            featureKey: 'approval_ai_summary',
            capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_GOVERNANCE_INTELLIGENCE,
            payload: $summary,
            provenance: $this->providerArtifactProvenance($summary, AiStatusSchema::ENDPOINT_GROUP_COMPARISON_AWARD),
        );
    }
}
