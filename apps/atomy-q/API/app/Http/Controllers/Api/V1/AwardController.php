<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Throwable;
use App\Adapters\Ai\Contracts\ComparisonAwardAiClientInterface;
use App\Adapters\Ai\Contracts\AiRuntimeStatusInterface;
use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Api\V1\Concerns\InteractsWithAiAvailability;
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
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;

final class AwardController extends Controller
{
    use ExtractsAuthContext;
    use InteractsWithAiAvailability;

    public function __construct(
        private readonly ComparisonAwardAiClientInterface $comparisonAwardAiClient,
    ) {}

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
     * GET /awards/:id/guidance
     * Scoped by tenant_id.
     */
    public function guidance(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        $award = $this->findAward($tenantId, $id);
        if ($award === null) {
            return response()->json(['message' => 'Award not found'], 404);
        }

        $comparisonRun = $award->comparisonRun()->where('tenant_id', $tenantId)->first();
        if ($comparisonRun === null) {
            return response()->json(['message' => 'Comparison run not found'], 404);
        }

        $storedGuidance = $this->storedAwardGuidance($comparisonRun, (string) $award->id);
        if ($storedGuidance !== null) {
            return response()->json([
                'data' => [
                    'ai_guidance' => $storedGuidance,
                ],
            ]);
        }

        if (! $this->aiCapabilityAvailable('award_ai_guidance')) {
            return response()->json([
                'data' => [
                    'ai_guidance' => $this->unavailableArtifactEnvelope(
                        featureKey: 'award_ai_guidance',
                        capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_AWARD_INTELLIGENCE,
                    ),
                ],
            ], 503);
        }

        try {
            $guidance = $this->comparisonAwardAiClient->awardGuidance([
                'tenant_id' => $tenantId,
                'award_id' => $award->id,
                'rfq_id' => $award->rfq_id,
                'comparison_run_id' => $award->comparison_run_id,
                'award' => $this->serializeAward($award),
                'comparison_context' => $this->serializeComparisonContext($comparisonRun),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'data' => [
                    'ai_guidance' => $this->unavailableArtifactEnvelope(
                        featureKey: 'award_ai_guidance',
                        capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_AWARD_INTELLIGENCE,
                    ),
                ],
            ], 503);
        }

        $artifact = $this->artifactEnvelope(
            featureKey: 'award_ai_guidance',
            capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_AWARD_INTELLIGENCE,
            payload: $guidance,
            provenance: $this->providerArtifactProvenance($guidance, AiStatusSchema::ENDPOINT_GROUP_COMPARISON_AWARD),
        );
        $this->persistAwardGuidanceArtifact($comparisonRun, $award, $artifact);

        return response()->json([
            'data' => [
                'ai_guidance' => [
                    ...$artifact,
                ],
            ],
        ]);
    }

    /**
     * GET /awards/:id/debrief-draft/:vendorId
     */
    public function debriefDraft(Request $request, string $id, string $vendorId): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        $award = $this->findAward($tenantId, $id);
        if ($award === null) {
            return response()->json(['message' => 'Award not found'], 404);
        }

        if ($vendorId === $award->vendor_id) {
            return response()->json(['message' => 'Winning vendor does not require a debrief draft'], 422);
        }

        $comparisonRun = $award->comparisonRun()->where('tenant_id', $tenantId)->first();
        if ($comparisonRun === null) {
            return response()->json(['message' => 'Comparison run not found'], 404);
        }

        $losingSubmission = QuoteSubmission::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $award->rfq_id)
            ->where('vendor_id', $vendorId)
            ->first();
        if ($losingSubmission === null) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        $storedDraft = $this->storedAwardDebriefDraft($comparisonRun, (string) $award->id, $vendorId);
        if ($storedDraft !== null) {
            return response()->json([
                'data' => [
                    'ai_debrief_draft' => $storedDraft,
                ],
            ]);
        }

        if (! $this->aiCapabilityAvailable('award_ai_guidance')) {
            return response()->json([
                'data' => [
                    'ai_debrief_draft' => $this->unavailableArtifactEnvelope(
                        featureKey: 'award_ai_guidance',
                        capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_AWARD_INTELLIGENCE,
                    ),
                ],
            ], 503);
        }

        try {
            $draft = $this->comparisonAwardAiClient->awardDebriefDraft([
                'tenant_id' => $tenantId,
                'award_id' => $award->id,
                'rfq_id' => $award->rfq_id,
                'comparison_run_id' => $award->comparison_run_id,
                'vendor_id' => $vendorId,
                'award' => $this->serializeAward($award),
                'losing_vendor' => [
                    'vendor_id' => (string) $losingSubmission->vendor_id,
                    'vendor_name' => $losingSubmission->vendor_name,
                    'quote_submission_id' => (string) $losingSubmission->id,
                ],
                'comparison_context' => $this->serializeComparisonContext($comparisonRun),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'data' => [
                    'ai_debrief_draft' => $this->unavailableArtifactEnvelope(
                        featureKey: 'award_ai_guidance',
                        capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_AWARD_INTELLIGENCE,
                    ),
                ],
            ], 503);
        }

        $artifact = $this->artifactEnvelope(
            featureKey: 'award_ai_guidance',
            capabilityGroup: AiStatusSchema::CAPABILITY_GROUP_AWARD_INTELLIGENCE,
            payload: $draft,
            provenance: $this->providerArtifactProvenance($draft, AiStatusSchema::ENDPOINT_GROUP_COMPARISON_AWARD),
        );
        $this->persistAwardDebriefDraftArtifact($comparisonRun, $award, $vendorId, $artifact);

        return response()->json([
            'data' => [
                'ai_debrief_draft' => $artifact,
            ],
        ]);
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
                'generated_at' => now()->toIso8601String(),
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
    private function storedAwardGuidance(ComparisonRun $comparisonRun, string $awardId): ?array
    {
        $responsePayload = $comparisonRun->response_payload ?? [];
        if (! is_array($responsePayload)) {
            return null;
        }

        $artifact = $responsePayload['ai_artifacts']['award_guidance'][$awardId] ?? null;

        return is_array($artifact) ? $artifact : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function storedAwardDebriefDraft(ComparisonRun $comparisonRun, string $awardId, string $vendorId): ?array
    {
        $responsePayload = $comparisonRun->response_payload ?? [];
        if (! is_array($responsePayload)) {
            return null;
        }

        $artifact = $responsePayload['ai_artifacts']['award_debrief_draft'][$awardId][$vendorId] ?? null;

        return is_array($artifact) ? $artifact : null;
    }

    /**
     * @param array<string, mixed> $artifact
     */
    private function persistAwardGuidanceArtifact(ComparisonRun $comparisonRun, Award $award, array $artifact): void
    {
        $responsePayload = $comparisonRun->response_payload ?? [];
        if (! is_array($responsePayload)) {
            $responsePayload = [];
        }

        $artifacts = $responsePayload['ai_artifacts'] ?? [];
        if (! is_array($artifacts)) {
            $artifacts = [];
        }

        $guidanceArtifacts = $artifacts['award_guidance'] ?? [];
        if (! is_array($guidanceArtifacts)) {
            $guidanceArtifacts = [];
        }

        $guidanceArtifacts[(string) $award->id] = $artifact;
        $artifacts['award_guidance'] = $guidanceArtifacts;
        $responsePayload['ai_artifacts'] = $artifacts;
        $comparisonRun->response_payload = $responsePayload;
        $comparisonRun->save();

        app(DecisionTrailRecorder::class)->recordAiArtifactGenerated(
            tenantId: (string) $award->tenant_id,
            rfqId: (string) $award->rfq_id,
            comparisonRunId: (string) $award->comparison_run_id,
            eventType: 'award_ai_guidance_generated:' . (string) $award->id,
            summary: [
                'artifact_kind' => 'award_ai_guidance',
                'artifact_origin' => 'provider_drafted',
                'feature_key' => 'award_ai_guidance',
                'award_id' => (string) $award->id,
                'artifact' => $artifact,
                'provenance' => is_array($artifact['provenance'] ?? null) ? $artifact['provenance'] : [],
            ],
        );
    }

    /**
     * @param array<string, mixed> $artifact
     */
    private function persistAwardDebriefDraftArtifact(
        ComparisonRun $comparisonRun,
        Award $award,
        string $vendorId,
        array $artifact,
    ): void {
        $responsePayload = $comparisonRun->response_payload ?? [];
        if (! is_array($responsePayload)) {
            $responsePayload = [];
        }

        $artifacts = $responsePayload['ai_artifacts'] ?? [];
        if (! is_array($artifacts)) {
            $artifacts = [];
        }

        $draftArtifacts = $artifacts['award_debrief_draft'] ?? [];
        if (! is_array($draftArtifacts)) {
            $draftArtifacts = [];
        }

        $awardDraftArtifacts = $draftArtifacts[(string) $award->id] ?? [];
        if (! is_array($awardDraftArtifacts)) {
            $awardDraftArtifacts = [];
        }

        $awardDraftArtifacts[$vendorId] = $artifact;
        $draftArtifacts[(string) $award->id] = $awardDraftArtifacts;
        $artifacts['award_debrief_draft'] = $draftArtifacts;
        $responsePayload['ai_artifacts'] = $artifacts;
        $comparisonRun->response_payload = $responsePayload;
        $comparisonRun->save();

        app(DecisionTrailRecorder::class)->recordAiArtifactGenerated(
            tenantId: (string) $award->tenant_id,
            rfqId: (string) $award->rfq_id,
            comparisonRunId: (string) $award->comparison_run_id,
            eventType: 'award_ai_debrief_draft_generated',
            summary: [
                'artifact_kind' => 'award_ai_debrief_draft',
                'artifact_origin' => 'provider_drafted',
                'feature_key' => 'award_ai_guidance',
                'award_id' => (string) $award->id,
                'vendor_id' => $vendorId,
                'artifact' => $artifact,
                'provenance' => is_array($artifact['provenance'] ?? null) ? $artifact['provenance'] : [],
            ],
        );
    }
}
