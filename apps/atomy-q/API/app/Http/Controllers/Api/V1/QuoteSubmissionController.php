<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Api\V1\Concerns\InteractsWithAiAvailability;
use App\Http\Controllers\Controller;
use App\Http\Idempotency\IdempotencyCompletion;
use App\Http\Requests\ManualNormalizationSourceLineRequest;
use App\Http\Requests\QuoteSubmissionStatusRequest;
use App\Http\Requests\QuoteSubmissionUploadRequest;
use App\Jobs\ProcessQuoteSubmissionJob;
use App\Models\NormalizationSourceLine;
use App\Models\QuoteSubmission;
use App\Models\Rfq;
use App\Models\RfqLineItem;
use App\Services\QuoteIntake\DecisionTrailRecorder;
use App\Services\QuoteIntake\QuoteSubmissionReadinessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Nexus\IntelligenceOperations\DTOs\AiCapabilityStatus;
use Nexus\IntelligenceOperations\DTOs\AiStatusSchema;
use Nexus\Idempotency\Contracts\IdempotencyServiceInterface;
use Nexus\QuoteIngestion\QuoteIngestionOrchestrator;
use Throwable;

final class QuoteSubmissionController extends Controller
{
    use ExtractsAuthContext;
    use InteractsWithAiAvailability;

    public function __construct(
        private readonly QuoteSubmissionReadinessService $readiness,
        private readonly DecisionTrailRecorder $decisionTrail,
    ) {}

    /**
     * GET /quote-submissions
     * Params: status, rfq_id, vendor_id, page, per_page
     * Scoped by tenant_id.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $pagination = $this->paginationParams($request);

        $query = QuoteSubmission::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $query->where('status', (string) $status);
        }

        if ($rfqId = $request->query('rfq_id')) {
            $query->where('rfq_id', (string) $rfqId);
        }

        if ($vendorId = $request->query('vendor_id')) {
            $query->where('vendor_id', (string) $vendorId);
        }

        $paginator = $query->paginate($pagination['per_page'], ['*'], 'page', $pagination['page']);
        $rows = $paginator->getCollection()
            ->map(fn (QuoteSubmission $submission): array => $this->quoteSubmissionData($submission))
            ->values();

        return response()->json([
            'data' => $rows,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    /**
     * POST /quote-submissions/upload
     * Scoped by tenant_id. Requires rfq_id, vendor_id, and an uploaded file.
     */
    public function upload(QuoteSubmissionUploadRequest $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $validated = $request->validated();

        $rfqId = (string) $validated['rfq_id'];
        $rfq = Rfq::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($builder) use ($rfqId): void {
                $builder->where('id', $rfqId)->orWhere('rfq_number', $rfqId);
            })
            ->first();

        if ($rfq === null) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }

        $vendorId = (string) $validated['vendor_id'];
        $vendorName = (string) ($validated['vendor_name'] ?? 'Vendor');
        $filePath = null;
        $fileType = null;
        $file = $request->file('file');
        if ($file === null) {
            throw ValidationException::withMessages([
                'file' => ['The file field is required.'],
            ]);
        }

        $filePath = $file->store('quote-submissions', 'local');
        $fileType = $file->getMimeType();

        $qs = new QuoteSubmission();
        $qs->tenant_id = $tenantId;
        $qs->rfq_id = $rfq->id;
        $qs->vendor_id = $vendorId;
        $qs->vendor_name = $vendorName;
        $qs->uploaded_by = $this->userId($request);
        $manualExtractionContinuity = $this->shouldUseManualExtractionContinuity();
        $qs->status = $manualExtractionContinuity ? 'needs_review' : 'uploaded';
        $qs->file_path = $filePath;
        $qs->file_type = $fileType;
        $qs->original_filename = $file->getClientOriginalName();
        $qs->submitted_at = now();
        $qs->confidence = $manualExtractionContinuity ? null : 85.0;
        $qs->line_items_count = 0;
        $qs->warnings_count = 0;
        $qs->errors_count = $manualExtractionContinuity ? 1 : 0;
        if ($manualExtractionContinuity) {
            $this->applyExtractionUnavailable($qs);
        }
        $qs->save();

        if (!$manualExtractionContinuity) {
            $this->dispatchProcessingJob($qs);
        }
        $qs->refresh();

        return response()->json([
            'data' => $this->quoteSubmissionData($qs),
        ], 201);
    }

    private function dispatchProcessingJob(QuoteSubmission $submission): void
    {
        $defaultConnection = (string) config('queue.default');
        $driver = (string) config('queue.connections.' . $defaultConnection . '.driver');
        $shouldRunSync = $driver === 'sync';

        if ($shouldRunSync) {
            $job = new ProcessQuoteSubmissionJob($submission->id);
            $job->runSync(app(QuoteIngestionOrchestrator::class));
        } else {
            ProcessQuoteSubmissionJob::dispatch($submission->id);
        }
    }

    /**
     * GET /quote-submissions/:id
     * Two-tab detail data.
     * Scoped by tenant_id.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $submission = QuoteSubmission::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        if ($submission === null) {
            return response()->json(['message' => 'Quote submission not found'], 404);
        }

        return response()->json([
            'data' => $this->quoteSubmissionData($submission) + [
                'tab_overview' => [],
                'tab_details' => [],
            ],
        ]);
    }

    /**
     * PATCH /quote-submissions/:id/status
     * Update quote ingestion state.
     * Scoped by tenant_id.
     */
    public function updateStatus(QuoteSubmissionStatusRequest $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $validated = $request->validated();

        $qs = QuoteSubmission::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        if ($qs === null) {
            return response()->json(['message' => 'Quote submission not found'], 404);
        }

        $requestedStatus = (string) $validated['status'];
        $status = $this->normalizeStatus($requestedStatus);

        if (!$this->isAllowedStatusTransition((string) $qs->status, $status) && !$this->isCompatibleLegacyTransition((string) $qs->status, $requestedStatus)) {
            throw ValidationException::withMessages([
                'status' => ['Unsupported quote submission status transition.'],
            ]);
        }

        $qs->status = $status;
        $qs->save();

        return response()->json([
            'data' => [
                'id' => $qs->id,
                'status' => $qs->status,
            ],
        ]);
    }

    public function storeSourceLine(
        ManualNormalizationSourceLineRequest $request,
        string $id,
        IdempotencyServiceInterface $idempotency,
    ): JsonResponse
    {
        try {
            $tenantId = $this->tenantId($request);
            $submission = $this->quoteSubmissionForTenant($tenantId, $id);
            if ($submission === null) {
                IdempotencyCompletion::fail($request, $idempotency);

                return response()->json(['message' => 'Quote submission not found'], 404);
            }

            $validated = $request->validated();
            $rfqLine = $this->rfqLineForSubmission($tenantId, $submission, $validated['rfq_line_item_id'] ?? null);
            if (($validated['rfq_line_item_id'] ?? null) !== null && $rfqLine === null) {
                IdempotencyCompletion::fail($request, $idempotency);

                return response()->json(['message' => 'RFQ line not found'], 404);
            }

            [$sourceLine, $meta] = DB::transaction(function () use ($tenantId, $submission, $rfqLine, $validated, $request): array {
                $sourceLine = new NormalizationSourceLine();
                $sourceLine->tenant_id = $tenantId;
                $sourceLine->quote_submission_id = $submission->id;
                $sourceLine->rfq_line_item_id = $rfqLine?->id;
                $this->applyManualSourceLineData($sourceLine, $validated, $this->userId($request));
                $sourceLine->source_vendor = $submission->vendor_name;
                $sourceLine->sort_order = array_key_exists('sort_order', $validated)
                    ? (int) $validated['sort_order']
                    : $this->nextSourceLineSortOrder($submission);
                $sourceLine->save();

                $meta = $this->refreshManualReadiness($submission);
                $this->recordManualSourceLineDecision($submission, 'manual_source_line_created', $sourceLine->id);

                return [$sourceLine->refresh(), $meta];
            });

            $response = response()->json([
                'data' => $this->manualSourceLineData($sourceLine),
                'meta' => $meta,
            ], 201);

            return IdempotencyCompletion::succeed($request, $idempotency, $response);
        } catch (ValidationException $e) {
            IdempotencyCompletion::fail($request, $idempotency);
            throw $e;
        } catch (Throwable $e) {
            IdempotencyCompletion::fail($request, $idempotency);
            throw $e;
        }
    }

    public function updateSourceLine(ManualNormalizationSourceLineRequest $request, string $id, string $sourceLineId): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $submission = $this->quoteSubmissionForTenant($tenantId, $id);
        if ($submission === null) {
            return response()->json(['message' => 'Quote submission not found'], 404);
        }

        $sourceLine = NormalizationSourceLine::query()
            ->where('tenant_id', $tenantId)
            ->where('quote_submission_id', $submission->id)
            ->where('id', $sourceLineId)
            ->first();
        if ($sourceLine === null) {
            return response()->json(['message' => 'Source line not found'], 404);
        }

        $validated = $request->validated();
        if (array_key_exists('rfq_line_item_id', $validated)) {
            $rfqLine = $this->rfqLineForSubmission($tenantId, $submission, $validated['rfq_line_item_id']);
            if ($validated['rfq_line_item_id'] !== null && $rfqLine === null) {
                return response()->json(['message' => 'RFQ line not found'], 404);
            }

            $sourceLine->rfq_line_item_id = $rfqLine?->id;
        }

        [$sourceLine, $meta] = DB::transaction(function () use ($sourceLine, $submission, $validated, $request): array {
            $this->applyManualSourceLineData($sourceLine, $validated, $this->userId($request));
            $sourceLine->source_vendor = $submission->vendor_name;
            if (array_key_exists('sort_order', $validated)) {
                $sourceLine->sort_order = (int) $validated['sort_order'];
            }
            $sourceLine->save();

            $meta = $this->refreshManualReadiness($submission);
            $this->recordManualSourceLineDecision($submission, 'manual_source_line_updated', $sourceLine->id);

            return [$sourceLine->refresh(), $meta];
        });

        return response()->json([
            'data' => $this->manualSourceLineData($sourceLine),
            'meta' => $meta,
        ]);
    }

    public function destroySourceLine(Request $request, string $id, string $sourceLineId): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $submission = $this->quoteSubmissionForTenant($tenantId, $id);
        if ($submission === null) {
            return response()->json(['message' => 'Quote submission not found'], 404);
        }

        $sourceLine = NormalizationSourceLine::query()
            ->where('tenant_id', $tenantId)
            ->where('quote_submission_id', $submission->id)
            ->where('id', $sourceLineId)
            ->first();
        if ($sourceLine === null) {
            return response()->json(['message' => 'Source line not found'], 404);
        }

        $meta = DB::transaction(function () use ($sourceLine, $submission, $sourceLineId): array {
            $sourceLine->conflicts()->delete();
            $sourceLine->delete();

            $meta = $this->refreshManualReadiness($submission);
            $this->recordManualSourceLineDecision($submission, 'manual_source_line_deleted', $sourceLineId);

            return $meta;
        });

        return response()->json([
            'data' => [
                'id' => $sourceLineId,
                'deleted' => true,
            ],
            'meta' => $meta,
        ]);
    }

    /**
     * POST /quote-submissions/:id/replace
     * Scoped by tenant_id.
     */
    public function replace(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'status' => 'replaced',
            ],
        ]);
    }

    /**
     * POST /quote-submissions/:id/reparse
     * Scoped by tenant_id.
     */
    public function reparse(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        
        $qs = QuoteSubmission::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        if ($qs === null) {
            return response()->json(['message' => 'Quote submission not found'], 404);
        }

        if (in_array($qs->status, ['extracting', 'normalizing'], true)) {
            return response()->json([
                'data' => [
                    'id' => $qs->id,
                    'status' => $qs->status,
                    'message' => 'Processing already in progress',
                ],
            ], 202);
        }

        $qs->status = 'uploaded';
        $qs->error_code = null;
        $qs->error_message = null;
        $qs->processing_started_at = null;
        $qs->processing_completed_at = null;
        $qs->retry_count = 0;
        $qs->save();

        $sourceLines = $qs->normalizationSourceLines()
            ->with('conflicts')
            ->get();

        foreach ($sourceLines as $line) {
            $raw = is_array($line->raw_data) ? $line->raw_data : [];
            $override = $raw['override'] ?? null;
            $hasOverride = match (true) {
                $override === null, $override === '', $override === [] => false,
                is_string($override) => trim($override) !== '',
                default => true,
            };

            if ($hasOverride) {
                continue;
            }

            $hasResolvedConflict = $line->conflicts->contains(
                static function ($c): bool {
                    $res = $c->resolution;
                    return match (true) {
                        $res === null, $res === '', $res === [] => false,
                        is_string($res) => trim($res) !== '',
                        default => true,
                    };
                }
            );
            if ($hasResolvedConflict) {
                continue;
            }

            $isMapped = $line->rfq_line_item_id !== null;
            $hasUnresolvedConflict = $line->conflicts->contains(
                static function ($c): bool {
                    $res = $c->resolution;
                    return match (true) {
                        $res === null, $res === '', $res === [] => true,
                        is_string($res) => trim($res) === '',
                        default => false,
                    };
                }
            );

            // Delta reparse: only delete lines that are unmapped or still unresolved, and have no manual override.
            if (!$isMapped || $hasUnresolvedConflict) {
                $line->conflicts()->delete();
                $line->delete();
            }
        }

        $this->dispatchProcessingJob($qs);

        return response()->json([
            'data' => [
                'id' => $qs->id,
                'status' => 'extracting',
            ],
        ], 202);
    }

    /**
     * POST /quote-submissions/:id/assign
     * Scoped by tenant_id.
     */
    public function assign(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $id,
                'assigned_to' => $this->userId($request),
            ],
        ]);
    }

    /**
     * @return array<string, list<string>>
     */
    private function statusTransitions(): array
    {
        return [
            'uploaded' => ['extracting', 'failed'],
            'extracting' => ['extracted', 'failed'],
            'extracted' => ['normalizing', 'needs_review', 'failed'],
            'normalizing' => ['needs_review', 'ready', 'failed'],
            'needs_review' => ['normalizing', 'ready', 'failed'],
            'ready' => ['failed'],
            'failed' => ['uploaded', 'extracting'],
        ];
    }

    private function isAllowedStatusTransition(string $currentStatus, string $nextStatus): bool
    {
        $allowed = $this->statusTransitions();

        return in_array($nextStatus, $allowed[$currentStatus] ?? [], true);
    }

    private function normalizeStatus(string $status): string
    {
        return $status === 'accepted' ? 'ready' : $status;
    }

    private function isCompatibleLegacyTransition(string $currentStatus, string $requestedStatus): bool
    {
        return $currentStatus === 'uploaded' && $requestedStatus === 'accepted';
    }

    private function shouldUseManualExtractionContinuity(): bool
    {
        $mode = (string) config('atomy.ai.mode', AiStatusSchema::MODE_DETERMINISTIC);
        if (!in_array($mode, [AiStatusSchema::MODE_PROVIDER, AiStatusSchema::MODE_OFF], true)) {
            return false;
        }

        return !$this->aiCapabilityAvailable('quote_document_extraction');
    }

    private function markExtractionUnavailable(QuoteSubmission $submission): void
    {
        $this->applyExtractionUnavailable($submission);
        $submission->save();
    }

    private function applyExtractionUnavailable(QuoteSubmission $submission): void
    {
        $capabilityStatus = $this->aiCapabilityStatus('quote_document_extraction');
        $submission->status = 'needs_review';
        $submission->error_code = $capabilityStatus?->status === AiCapabilityStatus::STATUS_DISABLED
            ? 'EXTRACTION_DISABLED'
            : 'EXTRACTION_UNAVAILABLE';
        $submission->error_message = 'Quote document extraction is unavailable. Manual action required.';
        $submission->line_items_count = 0;
        $submission->warnings_count = 0;
        $submission->errors_count = 1;
        $submission->confidence = null;
        $submission->processing_completed_at = now();
    }

    private function quoteSubmissionForTenant(string $tenantId, string $id): ?QuoteSubmission
    {
        /** @var QuoteSubmission|null $submission */
        $submission = QuoteSubmission::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        return $submission;
    }

    private function rfqLineForSubmission(string $tenantId, QuoteSubmission $submission, mixed $rfqLineItemId): ?RfqLineItem
    {
        if ($rfqLineItemId === null || trim((string) $rfqLineItemId) === '') {
            return null;
        }

        /** @var RfqLineItem|null $rfqLine */
        $rfqLine = RfqLineItem::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $submission->rfq_id)
            ->where('id', (string) $rfqLineItemId)
            ->first();

        return $rfqLine;
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function applyManualSourceLineData(NormalizationSourceLine $sourceLine, array $validated, string $userId): void
    {
        $sourceLine->source_description = (string) $validated['source_description'];
        $sourceLine->source_quantity = array_key_exists('source_quantity', $validated) ? $validated['source_quantity'] : null;
        $sourceLine->source_uom = array_key_exists('source_uom', $validated) ? $validated['source_uom'] : null;
        $sourceLine->source_unit_price = array_key_exists('source_unit_price', $validated) ? $validated['source_unit_price'] : null;
        $sourceLine->ai_confidence = null;
        $sourceLine->taxonomy_code = null;
        $sourceLine->mapping_version = null;
        $rawData = is_array($sourceLine->raw_data) ? $sourceLine->raw_data : [];
        $existingProvenance = is_array($rawData['provenance'] ?? null) ? $rawData['provenance'] : [];
        $rawData['provenance'] = array_replace($existingProvenance, $this->manualProvenance($validated, $userId));
        $sourceLine->raw_data = $rawData;
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function manualProvenance(array $validated, string $userId): array
    {
        $provenance = [
            'origin' => 'manual',
            'user_id' => $userId,
            'timestamp' => now()->toAtomString(),
        ];

        foreach (['reason', 'note'] as $key) {
            if (isset($validated[$key]) && trim((string) $validated[$key]) !== '') {
                $provenance[$key] = (string) $validated[$key];
            }
        }

        return $provenance;
    }

    private function nextSourceLineSortOrder(QuoteSubmission $submission): int
    {
        QuoteSubmission::query()
            ->where('tenant_id', $submission->tenant_id)
            ->where('id', $submission->id)
            ->lockForUpdate()
            ->first();

        return ((int) NormalizationSourceLine::query()
            ->where('tenant_id', $submission->tenant_id)
            ->where('quote_submission_id', $submission->id)
            ->max('sort_order')) + 1;
    }

    /**
     * @return array<string, mixed>
     */
    private function refreshManualReadiness(QuoteSubmission $submission): array
    {
        $result = $this->readiness->evaluate($submission);
        $submission->status = $result['next_status'];
        $submission->line_items_count = $submission->normalizationSourceLines()->count();
        $submission->errors_count = $result['blocking_issue_count'];
        $submission->save();

        return [
            'quote_submission_id' => $submission->id,
            'quote_submission_status' => $submission->status,
            'has_blocking_issues' => $result['has_blocking_issues'],
            'blocking_issue_count' => $result['blocking_issue_count'],
            'manual_action_required' => $result['has_blocking_issues'],
        ];
    }

    private function recordManualSourceLineDecision(QuoteSubmission $submission, string $eventType, string $sourceLineId): void
    {
        $this->decisionTrail->recordManualSourceLineEvent(
            tenantId: (string) $submission->tenant_id,
            rfqId: (string) $submission->rfq_id,
            quoteSubmissionId: (string) $submission->id,
            sourceLineId: $sourceLineId,
            eventType: $eventType,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function manualSourceLineData(NormalizationSourceLine $sourceLine): array
    {
        $rawData = is_array($sourceLine->raw_data) ? $sourceLine->raw_data : [];
        $provenance = is_array($rawData['provenance'] ?? null) ? $rawData['provenance'] : ['origin' => 'manual'];

        return [
            'id' => $sourceLine->id,
            'quote_submission_id' => $sourceLine->quote_submission_id,
            'rfq_line_item_id' => $sourceLine->rfq_line_item_id,
            'source_description' => $sourceLine->source_description,
            'source_quantity' => $sourceLine->source_quantity !== null ? (string) $sourceLine->source_quantity : null,
            'source_uom' => $sourceLine->source_uom,
            'source_unit_price' => $sourceLine->source_unit_price !== null ? (string) $sourceLine->source_unit_price : null,
            'sort_order' => $sourceLine->sort_order,
            'origin' => (string) ($provenance['origin'] ?? 'manual'),
            'provenance' => $provenance,
            'provider_provenance' => null,
            'ai_confidence' => null,
            'taxonomy_code' => null,
            'mapping_version' => null,
            'raw_data' => $rawData,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function aiStatusData(): array
    {
        return [
            'extraction' => $this->capabilityData('quote_document_extraction'),
            'normalization' => $this->capabilityData('normalization_suggestions'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function capabilityData(string $featureKey): array
    {
        $status = $this->aiCapabilityStatus($featureKey);

        return [
            'feature_key' => $featureKey,
            'status' => $status?->status ?? AiStatusSchema::CAPABILITY_STATUS_UNAVAILABLE,
            'available' => $status?->available === true,
            'manual_action_required' => $status?->available !== true,
            'reason_codes' => $status?->reasonCodes ?? [],
            'provider_provenance' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function quoteSubmissionData(QuoteSubmission $submission): array
    {
        return [
            'id' => $submission->id,
            'rfq_id' => $submission->rfq_id,
            'vendor_id' => $submission->vendor_id,
            'vendor_name' => $submission->vendor_name,
            'uploaded_by' => $submission->uploaded_by,
            'status' => $submission->status,
            'file_path' => $submission->file_path,
            'file_type' => $submission->file_type,
            'original_filename' => $submission->original_filename,
            'blocking_issue_count' => $submission->blockingIssueCount(),
            'submitted_at' => $submission->submitted_at?->toAtomString(),
            'error_code' => $submission->error_code,
            'error_message' => $submission->error_message,
            'processing_started_at' => $submission->processing_started_at?->toAtomString(),
            'processing_completed_at' => $submission->processing_completed_at?->toAtomString(),
            'parsed_at' => $submission->parsed_at?->toAtomString(),
            'retry_count' => $submission->retry_count,
            'line_items_count' => $submission->line_items_count,
            'confidence' => $submission->confidence,
            'ai_status' => $this->aiStatusData(),
        ];
    }
}
