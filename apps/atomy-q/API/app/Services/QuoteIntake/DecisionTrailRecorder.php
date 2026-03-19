<?php

declare(strict_types=1);

namespace App\Services\QuoteIntake;

use App\Models\DecisionTrailEntry;

final readonly class DecisionTrailRecorder
{
    /**
     * Append a hash-chained decision trail row for a frozen comparison snapshot.
     *
     * @param array<string, mixed> $summary Tenant-safe, machine-readable summary (counts, ids); avoid PII.
     */
    public function recordSnapshotFrozen(
        string $tenantId,
        string $rfqId,
        string $comparisonRunId,
        array $summary,
    ): void {
        $previous = DecisionTrailEntry::query()
            ->where('comparison_run_id', $comparisonRunId)
            ->orderByDesc('sequence')
            ->first();

        $sequence = $previous === null ? 1 : ((int) $previous->sequence) + 1;
        $previousHash = $previous?->entry_hash ?? hash('sha256', 'genesis:' . $comparisonRunId);

        $payloadJson = json_encode($summary, JSON_THROW_ON_ERROR);
        $payloadHash = hash('sha256', $payloadJson);
        $entryHash = hash('sha256', $previousHash . $payloadHash . $sequence);

        DecisionTrailEntry::query()->create([
            'tenant_id' => $tenantId,
            'comparison_run_id' => $comparisonRunId,
            'rfq_id' => $rfqId,
            'sequence' => $sequence,
            'event_type' => 'comparison_snapshot_frozen',
            'payload_hash' => $payloadHash,
            'previous_hash' => $previousHash,
            'entry_hash' => $entryHash,
            'occurred_at' => now(),
        ]);
    }
}
