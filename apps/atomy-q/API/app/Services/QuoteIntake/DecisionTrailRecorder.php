<?php

declare(strict_types=1);

namespace App\Services\QuoteIntake;

use InvalidArgumentException;

use Illuminate\Support\Facades\DB;

use App\Models\DecisionTrailEntry;

/**
 * Records buyer-visible quote-normalization decisions in the RFQ decision trail.
 *
 * The recorder converts manual source-line and conflict-resolution activity into
 * immutable trail entries so comparison and award review can explain how quote
 * data was produced or overridden.
 */
final readonly class DecisionTrailRecorder implements DecisionTrailRecorderInterface
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

    public function recordEvidencePackFinalized(
        string $tenantId,
        string $rfqId,
        string $bundleId,
        string $checksum,
        string $actorId,
    ): DecisionTrailEntry {
        return $this->record(
            tenantId: $tenantId,
            rfqId: $rfqId,
            comparisonRunId: $rfqId,
            eventType: 'evidence_pack_finalized',
            summary: [
                'bundle_id' => $bundleId,
                'checksum' => $checksum,
                'actor_id' => $actorId,
            ],
        );
    }

    /**
     * @param array<string, mixed> $summary Tenant-safe, machine-readable summary (counts, ids); avoid PII.
     */
    public function recordVendorRecommendationGenerated(
        string $tenantId,
        string $rfqId,
        array $summary,
        string $origin = 'ai_generated',
        string $featureKey = 'vendor_recommendation',
    ): void {
        $this->recordAiArtifactGenerated(
            tenantId: $tenantId,
            rfqId: $rfqId,
            comparisonRunId: $rfqId,
            eventType: 'vendor_recommendation_generated',
            summary: array_replace([
                'artifact_kind' => 'vendor_recommendation',
                'artifact_origin' => $origin,
                'feature_key' => $featureKey,
                'available' => true,
            ], $summary),
        );
    }

    /**
     * @param array<string, mixed> $summary Tenant-safe, machine-readable summary (counts, ids); avoid PII.
     */
    public function recordBuyerShortlistReplaced(
        string $tenantId,
        string $rfqId,
        array $summary,
    ): void {
        $this->record(
            tenantId: $tenantId,
            rfqId: $rfqId,
            comparisonRunId: $rfqId,
            eventType: 'buyer_shortlist_replaced',
            summary: array_replace([
                'artifact_kind' => 'buyer_shortlist',
                'artifact_origin' => 'user_confirmed_action',
                'feature_key' => 'requisition_selected_vendors',
                'event_type' => 'buyer_shortlist_replaced',
            ], $summary),
        );
    }

    /**
     * Record one of the AI artifact events used by the RFQ sourcing chain.
     *
     * Allowed values are `comparison_ai_overlay_generated`,
     * `vendor_recommendation_generated`,
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

    /**
     * @param array<string, mixed> $summary Tenant-safe, machine-readable summary (counts, ids) for manual normalization audit events.
     *     Allowed fields include `actor_user_id`, `actor_name`, `reason_code`, `note`,
     *     `provider_confidence`, `before`, `after`, `provider_suggested`, `origin`,
     *     `quote_submission_id`, `source_line_id`, `event_type`, and `timestamp`.
     */
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
     * @param array<string, mixed> $summary Tenant-safe, machine-readable summary (counts, ids); avoid PII unless the caller is a manual normalization audit event documented above.
     */
    private function record(
        string $tenantId,
        string $rfqId,
        string $comparisonRunId,
        string $eventType,
        array $summary,
    ): DecisionTrailEntry {
        return DB::transaction(static function () use ($tenantId, $rfqId, $comparisonRunId, $eventType, $summary): DecisionTrailEntry {
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

            return DecisionTrailEntry::query()->create([
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
        $exactNames = [
            'comparison_ai_overlay_generated',
            'award_ai_debrief_draft_generated',
            'vendor_recommendation_generated',
        ];

        if (in_array($eventType, $exactNames, true)) {
            return true;
        }

        $patterns = [
            '/^award_ai_guidance_generated:[^:]+$/',
            '/^approval_ai_summary_generated:[^:]+$/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $eventType) === 1) {
                return true;
            }
        }

        return false;
    }
}
