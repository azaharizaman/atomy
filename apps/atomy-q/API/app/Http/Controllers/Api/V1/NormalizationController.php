<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\NormalizationOverrideRequest;
use App\Http\Requests\NormalizationResolveConflictRequest;
use App\Models\NormalizationConflict;
use App\Models\NormalizationSourceLine;
use App\Models\QuoteSubmission;
use App\Models\Rfq;
use App\Models\RfqLineItem;
use App\Services\QuoteIntake\QuoteSubmissionReadinessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

final class NormalizationController extends Controller
{
    use ExtractsAuthContext;

    public function __construct(
        private readonly QuoteSubmissionReadinessService $readiness,
    ) {}

    private function rfqForTenant(string $tenantId, string $rfqId): ?Rfq
    {
        /** @var Rfq|null $rfq */
        $rfq = Rfq::query()->where('tenant_id', $tenantId)->where('id', $rfqId)->first();

        return $rfq;
    }

    private function applyReadinessToSubmission(QuoteSubmission $submission): void
    {
        $submission->refresh();
        $result = $this->readiness->evaluate($submission);
        $submission->status = $result['next_status'];
        $submission->save();
    }

    /**
     * @return array{rfq_id: string, has_blocking_issues: bool, blocking_issue_count: int}
     */
    private function rfqReadinessMeta(string $tenantId, string $rfqId): array
    {
        $submissions = QuoteSubmission::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $rfqId)
            ->get();

        $hasBlocking = false;
        $blockingTotal = 0;
        foreach ($submissions as $submission) {
            $r = $this->readiness->evaluate($submission);
            if ($r['has_blocking_issues']) {
                $hasBlocking = true;
            }
            $blockingTotal += $r['blocking_issue_count'];
        }

        return [
            'rfq_id' => $rfqId,
            'has_blocking_issues' => $hasBlocking,
            'blocking_issue_count' => $blockingTotal,
        ];
    }

    /** GET /normalization/{rfqId}/source-lines */
    public function sourceLines(Request $request, string $rfqId): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        if ($this->rfqForTenant($tenantId, $rfqId) === null) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }

        return response()->json([
            'data' => [],
            'meta' => ['rfq_id' => $rfqId],
        ]);
    }

    /** GET /normalization/{rfqId}/normalized-items */
    public function normalizedItems(Request $request, string $rfqId): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        if ($this->rfqForTenant($tenantId, $rfqId) === null) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }

        return response()->json([
            'data' => [],
            'meta' => ['rfq_id' => $rfqId],
        ]);
    }

    /** PUT /normalization/source-lines/{id}/mapping */
    public function updateMapping(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $validated = $request->validate([
            'rfq_line_id' => ['required', 'string'],
        ]);

        $line = NormalizationSourceLine::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();
        if ($line === null) {
            return response()->json(['message' => 'Source line not found'], 404);
        }

        $submission = $line->quoteSubmission;
        $rfqLine = RfqLineItem::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $validated['rfq_line_id'])
            ->where('rfq_id', $submission->rfq_id)
            ->first();
        if ($rfqLine === null) {
            return response()->json(['message' => 'RFQ line not found'], 404);
        }

        $line->rfq_line_item_id = $rfqLine->id;
        $line->save();

        $this->applyReadinessToSubmission($submission);

        return response()->json([
            'data' => [
                'id' => $line->id,
                'rfq_line_id' => $line->rfq_line_item_id,
                'mapped' => true,
            ],
            'meta' => $this->readiness->evaluate($submission),
        ]);
    }

    /** POST /normalization/{rfqId}/bulk-mapping */
    public function bulkMapping(Request $request, string $rfqId): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        if ($this->rfqForTenant($tenantId, $rfqId) === null) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }

        $validated = $request->validate([
            'mappings' => ['required', 'array', 'min:1'],
            'mappings.*.source_line_id' => ['required', 'string'],
            'mappings.*.rfq_line_id' => ['required', 'string'],
        ]);

        $submissionIds = [];
        $mappedCount = 0;
        foreach ($validated['mappings'] as $mapping) {
            $line = NormalizationSourceLine::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $mapping['source_line_id'])
                ->first();
            if ($line === null) {
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => ['mappings' => ['Unknown source line id.']],
                ], 422);
            }

            $submission = $line->quoteSubmission;
            if ((string) $submission->rfq_id !== $rfqId) {
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => ['mappings' => ['Source line does not belong to this RFQ.']],
                ], 422);
            }

            $rfqLine = RfqLineItem::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $mapping['rfq_line_id'])
                ->where('rfq_id', $rfqId)
                ->first();
            if ($rfqLine === null) {
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => ['mappings' => ['Unknown RFQ line id for this RFQ.']],
                ], 422);
            }

            $line->rfq_line_item_id = $rfqLine->id;
            $line->save();
            $submissionIds[(string) $submission->id] = true;
            ++$mappedCount;
        }

        foreach (array_keys($submissionIds) as $submissionId) {
            $sub = QuoteSubmission::query()->find($submissionId);
            if ($sub !== null) {
                $this->applyReadinessToSubmission($sub);
            }
        }

        return response()->json([
            'data' => [
                'rfq_id' => $rfqId,
                'mapped_count' => $mappedCount,
            ],
        ]);
    }

    /** PUT /normalization/source-lines/{id}/override */
    public function override(NormalizationOverrideRequest $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $validated = $request->validated();

        $line = NormalizationSourceLine::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();
        if ($line === null) {
            return response()->json(['message' => 'Source line not found'], 404);
        }

        $raw = $line->raw_data ?? [];
        $raw['override'] = $validated['override_data'];
        $line->raw_data = $raw;

        if (isset($validated['override_data']['unit_price']) && is_numeric($validated['override_data']['unit_price'])) {
            $line->source_unit_price = (string) $validated['override_data']['unit_price'];
        }

        $line->save();

        $submission = $line->quoteSubmission;
        $this->applyReadinessToSubmission($submission);

        return response()->json([
            'data' => [
                'id' => $id,
                'is_overridden' => true,
                'override_data' => $validated['override_data'],
                'issue_code' => $validated['issue_code'] ?? null,
            ],
            'meta' => $this->readiness->evaluate($submission),
        ]);
    }

    /** DELETE /normalization/source-lines/{id}/override */
    public function revertOverride(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        $line = NormalizationSourceLine::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();
        if ($line === null) {
            return response()->json(['message' => 'Source line not found'], 404);
        }

        $raw = $line->raw_data ?? [];
        unset($raw['override']);
        $line->raw_data = $raw;
        $line->save();

        $this->applyReadinessToSubmission($line->quoteSubmission);

        return response()->json(null, 204);
    }

    /** GET /normalization/{rfqId}/conflicts */
    public function conflicts(Request $request, string $rfqId): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        if ($this->rfqForTenant($tenantId, $rfqId) === null) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }

        $items = NormalizationConflict::query()
            ->where('tenant_id', $tenantId)
            ->whereHas('sourceLine.quoteSubmission', static function ($q) use ($rfqId): void {
                $q->where('rfq_id', $rfqId);
            })
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'data' => $items->map(static fn (NormalizationConflict $c) => [
                'id' => $c->id,
                'conflict_type' => $c->conflict_type,
                'resolution' => $c->resolution,
                'normalization_source_line_id' => $c->normalization_source_line_id,
            ])->values()->all(),
            'meta' => $this->rfqReadinessMeta($tenantId, $rfqId),
        ]);
    }

    /** PUT /normalization/conflicts/{id}/resolve */
    public function resolveConflict(NormalizationResolveConflictRequest $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $validated = $request->validated();

        $conflict = NormalizationConflict::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();
        if ($conflict === null) {
            return response()->json(['message' => 'Conflict not found'], 404);
        }

        $conflict->resolution = $validated['resolution'];
        $conflict->resolved_at = Carbon::now();
        $conflict->resolved_by = $this->userId($request);
        $conflict->save();

        $submission = $conflict->sourceLine->quoteSubmission;
        $this->applyReadinessToSubmission($submission);

        $fresh = $this->readiness->evaluate($submission);

        return response()->json([
            'data' => [
                'id' => $conflict->id,
                'status' => 'resolved',
                'resolution' => $conflict->resolution,
                'resolution_data' => $validated['resolution_data'] ?? [],
                'issue_code' => $validated['issue_code'] ?? null,
            ],
            'meta' => [
                'quote_submission_id' => $submission->id,
                'quote_submission_status' => $submission->status,
                'has_blocking_issues' => $fresh['has_blocking_issues'],
                'blocking_issue_count' => $fresh['blocking_issue_count'],
            ],
        ]);
    }

    /** POST /normalization/{rfqId}/lock */
    public function lock(Request $request, string $rfqId): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        if ($this->rfqForTenant($tenantId, $rfqId) === null) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }

        return response()->json([
            'data' => [
                'rfq_id' => $rfqId,
                'locked' => true,
                'locked_by' => $this->userId($request),
            ],
        ]);
    }

    /** POST /normalization/{rfqId}/unlock */
    public function unlock(Request $request, string $rfqId): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        if ($this->rfqForTenant($tenantId, $rfqId) === null) {
            return response()->json(['message' => 'RFQ not found'], 404);
        }

        return response()->json([
            'data' => [
                'rfq_id' => $rfqId,
                'locked' => false,
            ],
        ]);
    }
}
