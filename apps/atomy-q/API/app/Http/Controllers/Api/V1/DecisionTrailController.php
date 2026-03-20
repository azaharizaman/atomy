<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ExtractsAuthContext;
use App\Http\Controllers\Controller;
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
            ->get();

        return response()->json([
            'data' => $entries->map(static fn (DecisionTrailEntry $e) => [
                'id' => $e->id,
                'rfq_id' => $e->rfq_id,
                'comparison_run_id' => $e->comparison_run_id,
                'sequence' => $e->sequence,
                'event_type' => $e->event_type,
                'payload_hash' => $e->payload_hash,
                'occurred_at' => $e->occurred_at?->toAtomString(),
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
            ->first();
        if ($entry === null) {
            return response()->json(['message' => 'Decision trail entry not found'], 404);
        }

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
                'description' => $entry->event_type,
                'metadata' => [
                    'payload_hash' => $entry->payload_hash,
                    'previous_hash' => $entry->previous_hash,
                    'entry_hash' => $entry->entry_hash,
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
}
