<?php

declare(strict_types=1);

namespace App\Services\QuoteIntake;

use App\Models\DecisionTrailEntry;
use InvalidArgumentException;
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
     * Record one of the AI artifact events used by the RFQ sourcing chain.
     *
     * Allowed values are `comparison_ai_overlay_generated`,
     * `award_ai_guidance_generated:{awardId}`,
     * `award_ai_debrief_draft_generated`,
     * and `approval_ai_summary_generated:{approvalId}`.
     *
     * @param array{
     *     artifact_kind: string,
     *     artifact_origin: string,
     *     feature_key: string,
     *     award_id?: string,
     *     approval_id?: string,
     *     vendor_id?: string,
     *     available?: bool,
     *     provenance?: array<string, mixed>|null,
     *     artifact?: array<string, mixed>|null
     * } $summary Tenant-safe, machine-readable summary (counts, ids); avoid PII.
     */
    public function recordAiArtifactGenerated(
        string $tenantId,
        string $rfqId,
        string $comparisonRunId,
        string $eventType,
        array $summary,
    ): void {
        if (! $this->isAllowedAiArtifactEventType($eventType)) {
            throw new InvalidArgumentException('Unsupported AI artifact decision-trail event type: ' . $eventType);
        }

        $this->record(
            tenantId: $tenantId,
            rfqId: $rfqId,
            comparisonRunId: $comparisonRunId,
            eventType: $eventType,
            summary: $summary,
        );
    }

    public function recordManualSourceLineEvent(
        string $tenantId,
        string $rfqId,
        string $quoteSubmissionId,
        string $sourceLineId,
        string $eventType,
        array $summary = [],
    ): void {
        $this->record(
            tenantId: $tenantId,
            rfqId: $rfqId,
            comparisonRunId: $quoteSubmissionId,
            eventType: $eventType,
            summary: array_replace([
                'origin' => 'manual',
                'quote_submission_id' => $quoteSubmissionId,
                'source_line_id' => $sourceLineId,
                'event_type' => $eventType,
            ], $summary),
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
        DB::transaction(static function () use ($tenantId, $rfqId, $comparisonRunId, $eventType, $summary): void {
            $previous = DecisionTrailEntry::query()
                ->where('tenant_id', $tenantId)
                ->where('comparison_run_id', $comparisonRunId)
                ->orderByDesc('sequence')
                ->lockForUpdate()
                ->first();

            $sequence = max(1, ((int) ($previous?->sequence ?? 0)) + 1);
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
                'summary_payload' => $summary,
                'payload_hash' => $payloadHash,
                'previous_hash' => $previousHash,
                'entry_hash' => $entryHash,
                'occurred_at' => now(),
            ]);
        });
    }

    private function isAllowedAiArtifactEventType(string $eventType): bool
    {
        if ($eventType === 'comparison_ai_overlay_generated' || $eventType === 'award_ai_debrief_draft_generated') {
            return true;
        }

        if (preg_match('/^award_ai_guidance_generated:[^:]+$/', $eventType) === 1) {
            return true;
        }

        return preg_match('/^approval_ai_summary_generated:[^:]+$/', $eventType) === 1;
    }
}
