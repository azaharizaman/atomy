<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuoteSubmissionStatusRequest;
use App\Http\Requests\QuoteSubmissionUploadRequest;
use App\Jobs\ProcessQuoteSubmissionJob;
use App\Models\QuoteSubmission;
use App\Models\Rfq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Nexus\QuoteIngestion\QuoteIngestionOrchestrator;

final class QuoteSubmissionController extends Controller
{
    use ExtractsAuthContext;

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
        $qs->status = 'uploaded';
        $qs->file_path = $filePath;
        $qs->file_type = $fileType;
        $qs->original_filename = $file->getClientOriginalName();
        $qs->submitted_at = now();
        $qs->confidence = 85.0;
        $qs->line_items_count = 0;
        $qs->warnings_count = 0;
        $qs->errors_count = 0;
        $qs->save();

        $this->dispatchProcessingJob($qs);
        $qs->refresh();

        return response()->json([
            'data' => $this->quoteSubmissionData($qs),
        ], 201);
    }

    private function dispatchProcessingJob(QuoteSubmission $submission): void
    {
        $shouldRunSync = in_array(config('queue.default'), ['sync', 'null'], true)
            || app()->environment(['local', 'testing']);

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
            $hasOverride = array_key_exists('override', $raw);

            if ($hasOverride) {
                continue;
            }

            $hasResolvedConflict = $line->conflicts->contains(static fn ($c): bool => $c->resolution !== null);
            if ($hasResolvedConflict) {
                continue;
            }

            $isMapped = $line->rfq_line_item_id !== null;
            $hasUnresolvedConflict = $line->conflicts->contains(static fn ($c): bool => $c->resolution === null);

            // Delta reparse: only delete lines that are unmapped or still unresolved, and have no manual override.
            if (!$isMapped || $hasUnresolvedConflict) {
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
        ];
    }
}
