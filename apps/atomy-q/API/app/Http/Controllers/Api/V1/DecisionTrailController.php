<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
use App\Models\ComparisonRun;
use App\Models\DecisionTrailEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DecisionTrailController extends Controller
{
    use ExtractsAuthContext;

    /** GET /decision-trail */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        $pagination = $this->paginationParams($request);

        $query = DecisionTrailEntry::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('occurred_at')
            ->orderByDesc('created_at');

        $rfqFilter = $request->query('rfq_id');
        if (is_string($rfqFilter) && $rfqFilter !== '') {
            $query->where('rfq_id', $rfqFilter);
        }

        $total = $query->count();
        $entries = $query
            ->forPage($pagination['page'], $pagination['per_page'])
            ->with([
                'comparisonRun' => static function ($relation) use ($tenantId): void {
                    $relation
                        ->where('tenant_id', $tenantId)
                        ->select('id', 'tenant_id', 'response_payload');
                },
            ])
            ->get();

        return response()->json([
            'data' => $entries->map(fn (DecisionTrailEntry $e) => [
                'id' => $e->id,
                'rfq_id' => $e->rfq_id,
                'comparison_run_id' => $e->comparison_run_id,
                'sequence' => $e->sequence,
                'event_type' => $e->event_type,
                'payload_hash' => $e->payload_hash,
                'occurred_at' => $e->occurred_at?->toAtomString(),
                'metadata' => $this->trailMetadata($e),
            ])->values()->all(),
            'meta' => [
                'current_page' => $pagination['page'],
                'per_page' => $pagination['per_page'],
                'total' => $total,
                'filters' => [
                    'scope' => $request->query('scope'),
                    'type' => $request->query('type'),
                    'date_from' => $request->query('date_from'),
                    'date_to' => $request->query('date_to'),
                    'rfq_id' => $rfqFilter,
                ],
            ],
        ]);
    }

    /** GET /decision-trail/{id} */
    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        $entry = DecisionTrailEntry::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->with([
                'comparisonRun' => static function ($relation) use ($tenantId): void {
                    $relation
                        ->where('tenant_id', $tenantId)
                        ->select('id', 'tenant_id', 'response_payload');
                },
            ])
            ->first();
        if ($entry === null) {
            return response()->json(['message' => 'Decision trail entry not found'], 404);
        }

        $metadata = $this->trailMetadata($entry);

        return response()->json([
            'data' => [
                'id' => $entry->id,
                'tenant_id' => $tenantId,
                'rfq_id' => $entry->rfq_id,
                'comparison_run_id' => $entry->comparison_run_id,
                'sequence' => $entry->sequence,
                'scope' => 'rfq',
                'event_type' => $entry->event_type,
                'actor_id' => '',
                'description' => $metadata['description'] ?? $entry->event_type,
                'metadata' => [
                    'payload_hash' => $entry->payload_hash,
                    'previous_hash' => $entry->previous_hash,
                    'entry_hash' => $entry->entry_hash,
                    ...$metadata,
                ],
                'hash' => $entry->entry_hash,
                'previous_hash' => $entry->previous_hash,
                'created_at' => $entry->created_at?->toAtomString(),
            ],
        ]);
    }

    /** POST /decision-trail/verify */
    public function verify(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        return response()->json([
            'data' => [
                'verified' => true,
                'entries_checked' => DecisionTrailEntry::query()->where('tenant_id', $tenantId)->count(),
                'integrity_status' => 'valid',
                'verified_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /** POST /decision-trail/export */
    public function export(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'export_id' => 'stub-export-id',
                'format' => $request->input('format', 'json'),
                'status' => 'processing',
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function trailMetadata(DecisionTrailEntry $entry): array
    {
        $base = [
            'artifact_origin' => 'deterministic',
            'artifact_kind' => 'event',
            'description' => $entry->event_type,
        ];

        $exactHandlers = [
            'comparison_snapshot_frozen' => fn (DecisionTrailEntry $trailEntry): array => [
                'artifact_origin' => 'deterministic_fact',
                'artifact_kind' => 'comparison_snapshot',
                'description' => 'Comparison snapshot frozen',
            ],
            'comparison_ai_overlay_generated' => fn (DecisionTrailEntry $trailEntry): array => $this->artifactMetadata(
                artifactKind: 'comparison_ai_overlay',
                defaultDescription: 'Comparison AI overlay generated',
                artifact: $this->artifactFromEntry($trailEntry, ['ai_artifacts', 'ai_overlay'])
                    ?? $this->artifactFromEntry($trailEntry, ['ai_overlay']),
            ),
            'award_ai_debrief_draft_generated' => fn (DecisionTrailEntry $trailEntry): array => $this->awardDebriefMetadata($trailEntry),
            'award_created' => fn (DecisionTrailEntry $trailEntry): array => $this->awardWorkflowMetadata($trailEntry),
            'award_debriefed' => fn (DecisionTrailEntry $trailEntry): array => $this->awardWorkflowMetadata($trailEntry),
            'award_signed_off' => fn (DecisionTrailEntry $trailEntry): array => $this->awardWorkflowMetadata($trailEntry),
        ];

        if (isset($exactHandlers[$entry->event_type])) {
            return array_merge($base, $exactHandlers[$entry->event_type]($entry));
        }

        $prefixHandlers = [
            'award_ai_guidance_generated:' => function (DecisionTrailEntry $trailEntry) use ($base): array {
                $awardId = substr($trailEntry->event_type, strlen('award_ai_guidance_generated:'));
                if ($awardId === '') {
                    return $base;
                }

                return array_merge($base, $this->artifactMetadata(
                    artifactKind: 'award_ai_guidance',
                    defaultDescription: 'Award AI guidance generated',
                    artifact: $this->artifactFromEntry($trailEntry, ['ai_artifacts', 'award_guidance', $awardId]),
                    artifactId: $awardId,
                ));
            },
            'approval_ai_summary_generated:' => function (DecisionTrailEntry $trailEntry) use ($base): array {
                $approvalId = substr($trailEntry->event_type, strlen('approval_ai_summary_generated:'));
                if ($approvalId === '') {
                    return $base;
                }

                return array_merge($base, $this->artifactMetadata(
                    artifactKind: 'approval_ai_summary',
                    defaultDescription: 'Approval AI summary generated',
                    artifact: $this->artifactFromEntry($trailEntry, ['ai_artifacts', 'approval_summary', $approvalId]),
                    artifactId: $approvalId,
                ));
            },
        ];

        foreach ($prefixHandlers as $prefix => $handler) {
            if (str_starts_with($entry->event_type, $prefix)) {
                return $handler($entry);
            }
        }

        return $base;
    }

    /**
     * @return array<string, mixed>
     */
    private function awardDebriefMetadata(DecisionTrailEntry $entry): array
    {
        $summary = is_array($entry->summary_payload) ? $entry->summary_payload : [];
        $awardId = is_string($summary['award_id'] ?? null) ? $summary['award_id'] : null;
        $vendorId = is_string($summary['vendor_id'] ?? null) ? $summary['vendor_id'] : null;
        $artifact = null;
        if ($awardId !== null && $awardId !== '' && $vendorId !== null && $vendorId !== '') {
            $artifact = $this->artifactFromEntry($entry, ['ai_artifacts', 'award_debrief_draft', $awardId, $vendorId]);
        }

        return $this->artifactMetadata(
            artifactKind: 'award_ai_debrief_draft',
            defaultDescription: 'Award AI debrief draft generated',
            artifact: $artifact,
            artifactId: $vendorId,
            extra: [
                'award_id' => $awardId,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function awardWorkflowMetadata(DecisionTrailEntry $entry): array
    {
        return [
            'artifact_origin' => 'user_confirmed_action',
            'artifact_kind' => 'award_workflow_action',
            'description' => match ($entry->event_type) {
                'award_created' => 'Award created',
                'award_debriefed' => 'Award debrief drafted',
                'award_signed_off' => 'Award signed off',
                default => $entry->event_type,
            },
        ];
    }

    /**
     * @param array<int, string> $path
     * @return array<string, mixed>|null
     */
    private function artifactFromEntry(DecisionTrailEntry $entry, array $path): ?array
    {
        $summary = is_array($entry->summary_payload) ? $entry->summary_payload : [];
        $artifact = $this->artifactFromSummary($summary);
        if ($artifact !== null) {
            return $artifact;
        }

        return $this->artifactFromRun($entry->comparisonRun, $path);
    }

    /**
     * @param array<string, mixed> $summary
     * @return array<string, mixed>|null
     */
    private function artifactFromSummary(array $summary): ?array
    {
        $artifact = $summary['artifact'] ?? null;

        return is_array($artifact) ? $artifact : null;
    }

    /**
     * @param array<int, string> $path
     * @return array<string, mixed>|null
     */
    private function artifactFromRun(?ComparisonRun $comparisonRun, array $path): ?array
    {
        if (! $comparisonRun instanceof ComparisonRun) {
            return null;
        }

        $value = $comparisonRun->response_payload;
        if (! is_array($value)) {
            return null;
        }

        foreach ($path as $segment) {
            $value = is_array($value) ? ($value[$segment] ?? null) : null;
        }

        return is_array($value) ? $value : null;
    }

    /**
     * @param array<string, mixed>|null $artifact
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function artifactMetadata(
        string $artifactKind,
        string $defaultDescription,
        ?array $artifact = null,
        ?string $artifactId = null,
        array $extra = [],
    ): array {
        $provenance = is_array($artifact['provenance'] ?? null) ? $artifact['provenance'] : [];
        $available = $artifact['available'] ?? null;

        return array_merge([
            'artifact_kind' => $artifactKind,
            'artifact_subject_id' => $artifactId,
            'artifact_origin' => $available === true ? 'provider_drafted' : 'manual_continuity',
            'description' => $defaultDescription,
            'feature_key' => $artifact['feature_key'] ?? null,
            'available' => $available,
            'provenance' => $provenance,
        ], $extra);
    }
}
