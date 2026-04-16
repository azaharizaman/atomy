<?php

declare(strict_types=1);

namespace App\Services\QuoteIntake;

use App\Models\DecisionTrailEntry;
use Illuminate\Support\Facades\DB;

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
        $this->record(
            tenantId: $tenantId,
            rfqId: $rfqId,
            comparisonRunId: $comparisonRunId,
            eventType: 'comparison_snapshot_frozen',
            summary: $summary,
        );
    }

    /**
     * @param array<string, mixed> $summary Tenant-safe, machine-readable summary (counts, ids); avoid PII.
     */
    public function recordAwardDebriefed(
        string $tenantId,
        string $rfqId,
        string $comparisonRunId,
        array $summary,
    ): void {
        $this->record(
            tenantId: $tenantId,
            rfqId: $rfqId,
            comparisonRunId: $comparisonRunId,
            eventType: 'award_debriefed',
            summary: $summary,
        );
    }

    /**
     * @param array<string, mixed> $summary Tenant-safe, machine-readable summary (counts, ids); avoid PII.
     */
    public function recordAwardCreated(
        string $tenantId,
        string $rfqId,
        string $comparisonRunId,
        array $summary,
    ): void {
        $this->record(
            tenantId: $tenantId,
            rfqId: $rfqId,
            comparisonRunId: $comparisonRunId,
            eventType: 'award_created',
            summary: $summary,
        );
    }

    /**
     * @param array<string, mixed> $summary Tenant-safe, machine-readable summary (counts, ids); avoid PII.
     */
    public function recordAwardSignedOff(
        string $tenantId,
        string $rfqId,
        string $comparisonRunId,
        array $summary,
    ): void {
        $this->record(
            tenantId: $tenantId,
            rfqId: $rfqId,
            comparisonRunId: $comparisonRunId,
            eventType: 'award_signed_off',
            summary: $summary,
        );
    }

    /**
     * @param array<string, mixed> $summary Tenant-safe, machine-readable summary (counts, ids); avoid PII.
     */
    private function record(
        string $tenantId,
        string $rfqId,
        string $comparisonRunId,
        string $eventType,
        array $summary,
    ): void {
        DB::transaction(function () use ($tenantId, $rfqId, $comparisonRunId, $eventType, $summary): void {
            $previous = DecisionTrailEntry::query()
                ->where('tenant_id', $tenantId)
                ->where('comparison_run_id', $comparisonRunId)
                ->orderByDesc('sequence')
                ->lockForUpdate()
                ->first();

            $sequence = max(
                1,
                ((int) DecisionTrailEntry::query()
                    ->where('tenant_id', $tenantId)
                    ->where('comparison_run_id', $comparisonRunId)
                    ->lockForUpdate()
                    ->max('sequence')) + 1
            );
            $previousHash = $previous?->entry_hash ?? hash('sha256', 'genesis:' . $comparisonRunId);

            $payloadJson = json_encode($summary, JSON_THROW_ON_ERROR);
            $payloadHash = hash('sha256', $payloadJson);
            $entryHash = hash('sha256', $previousHash . $payloadHash . $sequence);

            DecisionTrailEntry::query()->create([
                'tenant_id' => $tenantId,
                'comparison_run_id' => $comparisonRunId,
                'rfq_id' => $rfqId,
                'sequence' => $sequence,
                'event_type' => $eventType,
                'payload_hash' => $payloadHash,
                'previous_hash' => $previousHash,
                'entry_hash' => $entryHash,
                'occurred_at' => now(),
            ]);
        });
    }
}
