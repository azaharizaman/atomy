<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Adapters\Ai\Contracts\ComparisonAwardAiClientInterface;
use App\Adapters\Ai\Contracts\AiRuntimeStatusInterface;
use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Api\V1\Concerns\InteractsWithAiAvailability;
use App\Http\Controllers\Controller;
use App\Http\Requests\ComparisonFinalizeRequest;
use App\Http\Requests\ComparisonPreviewRequest;
use App\Models\ComparisonRun;
use App\Models\QuoteSubmission;
use App\Models\Rfq;
use App\Services\QuoteIntake\ComparisonSnapshotService;
use App\Services\QuoteIntake\DecisionTrailRecorder;
use App\Services\QuoteIntake\QuoteSubmissionReadinessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Throwable;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Nexus\QuotationIntelligence\Contracts\BatchQuoteComparisonCoordinatorInterface;
use Nexus\QuotationIntelligence\Exceptions\ComparisonNotReadyException;
use Nexus\QuotationIntelligence\Exceptions\QuotationIntelligenceException;

final class ComparisonRunController extends Controller
{
    use ExtractsAuthContext;
    use InteractsWithAiAvailability;

    public function __construct(
        private readonly ComparisonSnapshotService $snapshotService,
        private readonly QuoteSubmissionReadinessService $readinessService,
        private readonly DecisionTrailRecorder $decisionTrail,
        private readonly BatchQuoteComparisonCoordinatorInterface $comparisonCoordinator,
        private readonly ComparisonAwardAiClientInterface $comparisonAwardAiClient,
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
                'ai_overlay' => $this->storedAiOverlay($run),
                'created_at' => $run->created_at?->toAtomString(),
            ],
        ]);
    }

    /**
     * POST /comparison-runs/preview
     * Scoped by tenant_id.
     */
    public function preview(ComparisonPreviewRequest $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $validated = $request->validated();
        $rfqId = (string) $validated['rfq_id'];

        $rfq = Rfq::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $rfqId)
            ->first();
        if ($rfq === null) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }

        $submissions = QuoteSubmission::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $rfqId)
            ->whereNotNull('file_path')
            ->where('file_path', '!=', '')
            ->get();

        if ($submissions->isEmpty()) {
            return response()->json([
                'error' => 'No quote submissions with source documents for this RFQ.',
                'details' => [],
            ], 422);
        }

        try {
            $comparison = $this->comparisonCoordinator->previewQuotes(
                $tenantId,
                $rfqId,
                $this->documentIds($submissions),
            );
        } catch (ComparisonNotReadyException $exception) {
            return $this->notReadyResponse($exception);
        } catch (QuotationIntelligenceException $exception) {
            return $this->comparisonWorkflowErrorResponse($exception);
        }

        $run = ComparisonRun::query()->create([
            'tenant_id' => $tenantId,
            'rfq_id' => $rfqId,
            'name' => 'Preview comparison',
            'description' => null,
            'idempotency_key' => null,
            'is_preview' => true,
            'created_by' => $this->userId($request),
            'request_payload' => ['rfq_id' => $rfqId],
            'matrix_payload' => $comparison['matrix'] ?? [],
            'scoring_payload' => $comparison['scoring'] ?? [],
            'approval_payload' => $comparison['approval'] ?? [],
            'response_payload' => [
                'documents_processed' => $comparison['documents_processed'] ?? count($submissions),
                'vendors' => $comparison['vendors'] ?? [],
            ],
            'readiness_payload' => $comparison['readiness'] ?? [],
            'status' => 'preview',
            'version' => 1,
            'expires_at' => null,
            'discarded_at' => null,
            'discarded_by' => null,
        ]);

        $aiOverlay = $this->buildComparisonAiOverlay(
            $tenantId,
            (string) $rfq->id,
            'preview',
            $comparison,
        );
        $this->persistComparisonAiOverlay($run, $aiOverlay);

        return response()->json([
            'data' => [
                'id' => $run->id,
                'rfq_id' => $rfqId,
                'status' => 'preview',
                'is_preview' => true,
                'matrix' => $run->matrix_payload,
                'readiness' => $run->readiness_payload,
                'approval' => $run->approval_payload,
                'ai_overlay' => $aiOverlay,
                'created_at' => $run->created_at?->toAtomString(),
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

        $submissionsWithDocuments = $submissions
            ->filter(static fn (QuoteSubmission $submission): bool => is_string($submission->file_path) && trim($submission->file_path) !== '')
            ->values();
        if ($submissionsWithDocuments->count() !== $submissions->count()) {
            return response()->json([
                'error' => 'All ready quote submissions must include source documents before freezing comparison.',
                'details' => [],
            ], 422);
        }

        try {
            $result = DB::transaction(function () use ($tenantId, $request, $rfq, $submissionsWithDocuments, $snapshot, $submissions): array {
                $comparison = $this->comparisonCoordinator->compareQuotes(
                    $tenantId,
                    (string) $rfq->id,
                    $this->documentIds($submissionsWithDocuments),
                );

                $run = ComparisonRun::query()->create([
                    'tenant_id' => $tenantId,
                    'rfq_id' => $rfq->id,
                    'name' => 'Final comparison',
                    'description' => null,
                    'idempotency_key' => null,
                    'is_preview' => false,
                    'created_by' => $this->userId($request),
                    'request_payload' => ['rfq_id' => $rfq->id],
                    'matrix_payload' => $comparison['matrix'] ?? [],
                    'scoring_payload' => $comparison['scoring'] ?? [],
                    'approval_payload' => $comparison['approval'] ?? [],
                    'response_payload' => ['snapshot' => $snapshot],
                    'readiness_payload' => $comparison['readiness'] ?? [],
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

                return [
                    'run' => $run,
                    'comparison' => $comparison,
                ];
            });
        } catch (ComparisonNotReadyException $exception) {
            return $this->notReadyResponse($exception);
        } catch (QuotationIntelligenceException $exception) {
            return $this->comparisonWorkflowErrorResponse($exception);
        }

        /** @var ComparisonRun $run */
        $run = $result['run'];
        $comparison = $result['comparison'];
        $aiOverlay = $this->buildComparisonAiOverlay(
            $tenantId,
            (string) $rfq->id,
            'final',
            $comparison,
            $snapshot,
        );
        $this->persistComparisonAiOverlay($run, $aiOverlay);

        return response()->json([
            'data' => [
                'id' => $run->id,
                'rfq_id' => $rfq->id,
                'status' => 'final',
                'snapshot' => $snapshot,
                'matrix' => $run->matrix_payload,
                'readiness' => $run->readiness_payload,
                'ai_overlay' => $aiOverlay,
            ],
        ], 201);
    }

    /**
     * GET /comparison-runs/:id/overlay
     * Scoped by tenant_id.
     */
    public function overlay(Request $request, string $id): JsonResponse
    {
        $run = $this->runForTenant($this->tenantId($request), $id);
        if ($run === null) {
            return response()->json(['message' => 'Comparison run not found'], 404);
        }

        return response()->json([
            'data' => [
                'ai_overlay' => $this->storedAiOverlay($run),
            ],
        ]);
    }

    /**
     * GET /comparison-runs/:id/matrix
     * Scoped by tenant_id.
     */
    public function matrix(Request $request, string $id): JsonResponse
    {
        $run = $this->runForTenant($this->tenantId($request), $id);
        if ($run === null) {
            return response()->json(['message' => 'Comparison run not found'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $run->id,
                'matrix' => $run->matrix_payload ?? [],
            ],
        ]);
    }

    /**
     * GET /comparison-runs/:id/readiness
     * Scoped by tenant_id.
     */
    public function readiness(Request $request, string $id): JsonResponse
    {
        $run = $this->runForTenant($this->tenantId($request), $id);
        if ($run === null) {
            return response()->json(['message' => 'Comparison run not found'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $run->id,
                'readiness' => $run->readiness_payload ?? [],
            ],
        ]);
    }

    /**
     * PATCH /comparison-runs/:id/scoring-model
     * Scoped by tenant_id.
     */
    public function updateScoringModel(Request $request, string $id): JsonResponse
    {
        return $this->deferredControlResponse($request, $id);
    }

    /**
     * POST /comparison-runs/:id/lock
     * Scoped by tenant_id.
     */
    public function lock(Request $request, string $id): JsonResponse
    {
        return $this->deferredControlResponse($request, $id);
    }

    /**
     * POST /comparison-runs/:id/unlock
     * Scoped by tenant_id.
     */
    public function unlock(Request $request, string $id): JsonResponse
    {
        return $this->deferredControlResponse($request, $id);
    }

    /**
     * @param Collection<int, QuoteSubmission> $submissions
     * @return array<int, string>
     */
    private function documentIds(Collection $submissions): array
    {
        return $submissions
            ->map(static fn (QuoteSubmission $submission): string => (string) $submission->id)
            ->values()
            ->all();
    }

    private function notReadyResponse(ComparisonNotReadyException $exception): JsonResponse
    {
        $readiness = $exception->getReadinessResult();

        return response()->json([
            'error' => 'Comparison is not ready.',
            'details' => $readiness->getBlockers(),
            'readiness' => [
                'is_ready' => $readiness->isReady(),
                'is_preview_only' => $readiness->isPreviewOnly(),
                'blockers' => $readiness->getBlockers(),
                'warnings' => $readiness->getWarnings(),
            ],
        ], 422);
    }

    private function comparisonWorkflowErrorResponse(QuotationIntelligenceException $exception): JsonResponse
    {
        return response()->json([
            'code' => class_basename($exception),
            'error' => $exception->getMessage(),
        ], 422);
    }

    /**
     * @param array<string, mixed> $comparison
     * @param array<string, mixed>|null $snapshot
     * @return array<string, mixed>
     */
    private function buildComparisonAiOverlay(
        string $tenantId,
        string $rfqId,
        string $mode,
        array $comparison,
        ?array $snapshot = null,
    ): array {
        $payload = [
            'tenant_id' => $tenantId,
            'rfq_id' => $rfqId,
            'mode' => $mode,
            'comparison' => $comparison,
            'snapshot' => $snapshot,
        ];

        if (! $this->aiCapabilityAvailable('comparison_ai_overlay')) {
            return $this->comparisonAiUnavailableOverlay();
        }

        try {
            $overlay = $this->comparisonAwardAiClient->comparisonOverlay($payload);
        } catch (Throwable $exception) {
            report($exception);

            return $this->comparisonAiUnavailableOverlay();
        }

        return $this->artifactEnvelope(
            featureKey: 'comparison_ai_overlay',
            capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_COMPARISON_INTELLIGENCE,
            available: true,
            payload: $overlay,
            provenance: $this->providerArtifactProvenance($overlay, AiStatusSchema::ENDPOINT_GROUP_COMPARISON_AWARD),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function comparisonAiUnavailableOverlay(): array
    {
        $capabilityStatus = $this->aiCapabilityStatus('comparison_ai_overlay');
        $statusSnapshot = $this->aiStatusSnapshot();

        return $this->artifactEnvelope(
            featureKey: 'comparison_ai_overlay',
            capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_COMPARISON_INTELLIGENCE,
            available: false,
            payload: null,
            provenance: [
                'source' => 'deterministic',
                'endpoint_group' => AiStatusSchema::ENDPOINT_GROUP_COMPARISON_AWARD,
                'provider_name' => $this->providerName(),
                'generated_at' => now()->toIso8601String(),
            ],
            status: $capabilityStatus?->status ?? AiStatusSchema::CAPABILITY_STATUS_UNAVAILABLE,
            fallbackUiMode: $capabilityStatus?->fallbackUiMode ?? AiStatusSchema::FALLBACK_UI_MODE_SHOW_UNAVAILABLE_MESSAGE,
            messageKey: $capabilityStatus?->messageKey ?? 'ai.capability.unavailable',
            reasonCodes: $capabilityStatus?->reasonCodes ?? $statusSnapshot->reasonCodes,
            diagnostics: $capabilityStatus?->diagnostics ?? ['mode' => $statusSnapshot->mode],
        );
    }

    private function persistComparisonAiOverlay(ComparisonRun $run, array $aiOverlay): void
    {
        $responsePayload = $run->response_payload ?? [];
        if (! is_array($responsePayload)) {
            $responsePayload = [];
        }

        $responsePayload['ai_overlay'] = $aiOverlay;
        $run->response_payload = $responsePayload;
        $run->save();

        $this->decisionTrail->recordAiArtifactGenerated(
            tenantId: (string) $run->tenant_id,
            rfqId: (string) $run->rfq_id,
            comparisonRunId: (string) $run->id,
            eventType: 'comparison_ai_overlay_generated',
            summary: [
                'artifact_kind' => 'comparison_ai_overlay',
                'artifact_origin' => $aiOverlay['available'] === true ? 'provider_drafted' : 'manual_continuity',
                'feature_key' => $aiOverlay['feature_key'] ?? 'comparison_ai_overlay',
                'available' => $aiOverlay['available'] ?? false,
                'artifact' => $aiOverlay,
                'provenance' => is_array($aiOverlay['provenance'] ?? null) ? $aiOverlay['provenance'] : [],
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function storedAiOverlay(ComparisonRun $run): array
    {
        $overlay = $run->response_payload['ai_overlay'] ?? null;
        if (is_array($overlay)) {
            return $overlay;
        }

        return $this->comparisonAiUnavailableOverlay();
    }

    /**
     * @param array<string, mixed>|null $payload
     * @param array<string, mixed>|null $provenance
     * @param array<int, string>|null $reasonCodes
     * @param array<string, mixed>|null $diagnostics
     * @return array<string, mixed>
     */
    private function artifactEnvelope(
        string $featureKey,
        string $capabilityGroup,
        bool $available,
        ?array $payload,
        ?array $provenance = null,
        string $status = AiStatusSchema::CAPABILITY_STATUS_AVAILABLE,
        ?string $fallbackUiMode = null,
        ?string $messageKey = null,
        ?array $reasonCodes = null,
        ?array $diagnostics = null,
    ): array {
        return [
            'feature_key' => $featureKey,
            'capability_group' => $capabilityGroup,
            'available' => $available,
            'status' => $status,
            'manual_continuity' => 'available',
            'fallback_ui_mode' => $fallbackUiMode,
            'message_key' => $messageKey,
            'reason_codes' => $reasonCodes ?? [],
            'diagnostics' => $diagnostics ?? [],
            'payload' => $payload,
            'provenance' => $provenance,
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
            'generated_at' => $provenance['generated_at'] ?? now()->toIso8601String(),
        ]);
    }

    private function providerName(): ?string
    {
        try {
            $providerName = app(AiRuntimeStatusInterface::class)->providerName();
        } catch (Throwable) {
            return null;
        }

        return is_string($providerName) && trim($providerName) !== '' ? trim($providerName) : null;
    }

    private function deferredControlResponse(Request $request, string $id): JsonResponse
    {
        $run = $this->runForTenant($this->tenantId($request), $id);
        if ($run === null) {
            return response()->json(['message' => 'Comparison run not found'], 404);
        }

        return response()->json([
            'code' => 'COMPARISON_CONTROL_DEFERRED',
            'error' => 'This comparison control is deferred to beta and is not available in alpha.',
            'data' => [
                'id' => (string) $run->id,
            ],
        ], 422);
    }

    private function runForTenant(string $tenantId, string $id): ?ComparisonRun
    {
        return ComparisonRun::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();
    }
}
