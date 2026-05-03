<?php

declare(strict_types=1);

namespace App\Services\EvidenceVault;

use App\Models\Approval;
use App\Models\Award;
use App\Models\ComparisonRun;
use App\Models\DecisionTrailEntry;
use App\Models\EvidenceBundle;
use App\Models\NormalizationConflict;
use App\Models\QuoteSubmission;
use App\Models\Rfq;

final readonly class EvidenceVaultSummaryService
{
    private const REQUIRED_DECISION_TRAIL_CODES = [
        'quote_sources',
        'final_comparison',
        'approval_trail',
        'award_signoff',
    ];

    /**
     * @return array<string, mixed>
     */
    public function summarize(string $tenantId, Rfq $rfq): array
    {
        $rfqId = (string) $rfq->id;

        $quoteSubmissions = QuoteSubmission::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $rfqId)
            ->whereIn('status', ['ready', 'final'])
            ->latest('created_at')
            ->get();

        $unresolvedConflictCount = NormalizationConflict::query()
            ->where('normalization_conflicts.tenant_id', $tenantId)
            ->whereNull('normalization_conflicts.resolution')
            ->whereNull('normalization_conflicts.resolved_at')
            ->whereHas('sourceLine.quoteSubmission', function ($query) use ($tenantId, $rfqId): void {
                $query
                    ->where('tenant_id', $tenantId)
                    ->where('rfq_id', $rfqId);
            })
            ->count();

        $finalComparison = ComparisonRun::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $rfqId)
            ->where('is_preview', false)
            ->whereIn('status', ['finalized', 'frozen', 'final'])
            ->latest('created_at')
            ->first();

        $approval = Approval::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $rfqId)
            ->whereIn('status', ['approved', 'rejected'])
            ->latest('created_at')
            ->first();

        $award = Award::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $rfqId)
            ->latest('created_at')
            ->first();

        $signedOffAward = $award?->signoff_at !== null ? $award : null;

        $bundle = EvidenceBundle::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $rfqId)
            ->where('type', 'award_justification')
            ->latest('created_at')
            ->first();

        $decisionTrailEntries = DecisionTrailEntry::query()
            ->where('tenant_id', $tenantId)
            ->where('rfq_id', $rfqId)
            ->orderBy('sequence')
            ->get();

        $decisionTrailCodes = $decisionTrailEntries
            ->pluck('event_type')
            ->map(static fn (mixed $eventType): string => (string) $eventType)
            ->all();

        $blockers = $this->blockers(
            quoteSubmissionCount: $quoteSubmissions->count(),
            unresolvedConflictCount: $unresolvedConflictCount,
            finalComparison: $finalComparison,
            approval: $approval,
            award: $award,
            signedOffAward: $signedOffAward,
            decisionTrailCodes: $decisionTrailCodes,
        );

        $ready = $blockers === [];
        $status = $this->awardPackStatus($ready, $bundle);
        $timeline = $this->timeline($finalComparison, $approval, $signedOffAward, $decisionTrailEntries);
        $sections = $this->sections($quoteSubmissions->count(), $finalComparison, $approval, $signedOffAward);

        return [
            'rfq' => [
                'id' => $rfqId,
                'title' => $rfq->title,
                'rfq_number' => $rfq->rfq_number,
            ],
            'award_pack' => [
                'status' => $status,
                'bundle_id' => $bundle?->id !== null ? (string) $bundle->id : null,
                'version' => $bundle?->version,
                'finalized_at' => $bundle?->finalized_at?->toAtomString(),
                'checksum' => $bundle?->checksum,
            ],
            'readiness' => [
                'ready' => $ready,
                'blockers' => $blockers,
            ],
            'timeline' => $timeline,
            'sections' => $sections,
            'actions' => [
                'can_finalize' => $ready && $status !== 'finalized',
                'can_export' => $status === 'finalized',
                'can_upload_supporting_evidence' => true,
            ],
        ];
    }

    /**
     * @param list<string> $decisionTrailCodes
     * @return list<array{code: string, message: string}>
     */
    private function blockers(
        int $quoteSubmissionCount,
        int $unresolvedConflictCount,
        ?ComparisonRun $finalComparison,
        ?Approval $approval,
        ?Award $award,
        ?Award $signedOffAward,
        array $decisionTrailCodes,
    ): array {
        $blockers = [];

        if ($finalComparison === null) {
            $blockers[] = $this->blocker('FINAL_COMPARISON_MISSING', 'Finalize and freeze the RFQ comparison before preparing the evidence pack.');
        }

        if ($quoteSubmissionCount === 0) {
            $blockers[] = $this->blocker('QUOTE_SOURCE_MISSING', 'At least one ready quote source is required for the evidence pack.');
        }

        if ($unresolvedConflictCount > 0) {
            $blockers[] = $this->blocker('NORMALIZATION_CONFLICT_UNRESOLVED', 'Resolve all quote normalization conflicts before finalization.');
        }

        if ($approval === null || $approval->status !== 'approved') {
            $blockers[] = $this->blocker('APPROVAL_DECISION_MISSING', 'Record an approved award decision before finalization.');
        }

        if ($award === null) {
            $blockers[] = $this->blocker('AWARD_MISSING', 'Create the award record before preparing the evidence pack.');
        } elseif ($signedOffAward === null) {
            $blockers[] = $this->blocker('AWARD_SIGNOFF_MISSING', 'Sign off the award before finalizing the evidence pack.');
        }

        if (array_diff(self::REQUIRED_DECISION_TRAIL_CODES, $decisionTrailCodes) !== []) {
            $blockers[] = $this->blocker('DECISION_TRAIL_INCOMPLETE', 'Complete the RFQ decision trail entries for quote sources, comparison, approval, and signoff.');
        }

        return $blockers;
    }

    /**
     * @return array{code: string, message: string}
     */
    private function blocker(string $code, string $message): array
    {
        return [
            'code' => $code,
            'message' => $message,
        ];
    }

    private function awardPackStatus(bool $ready, ?EvidenceBundle $bundle): string
    {
        if ($bundle?->status === 'finalized') {
            return 'finalized';
        }

        return $ready ? 'draft_ready' : 'not_ready';
    }

    /**
     * @param iterable<DecisionTrailEntry> $decisionTrailEntries
     * @return list<array{code: string, label: string, status: string, occurred_at: string|null}>
     */
    private function timeline(?ComparisonRun $finalComparison, ?Approval $approval, ?Award $signedOffAward, iterable $decisionTrailEntries): array
    {
        $timeline = [
            [
                'code' => 'final_comparison',
                'label' => 'Final comparison',
                'status' => $finalComparison === null ? 'missing' : 'complete',
                'occurred_at' => $finalComparison?->updated_at?->toAtomString(),
            ],
            [
                'code' => 'approval_trail',
                'label' => 'Approval decision',
                'status' => $approval?->status === 'approved' ? 'complete' : 'missing',
                'occurred_at' => $approval?->approved_at?->toAtomString(),
            ],
            [
                'code' => 'award_signoff',
                'label' => 'Award signoff',
                'status' => $signedOffAward === null ? 'missing' : 'complete',
                'occurred_at' => $signedOffAward?->signoff_at?->toAtomString(),
            ],
        ];

        foreach ($decisionTrailEntries as $entry) {
            $timeline[] = [
                'code' => (string) $entry->event_type,
                'label' => $this->label((string) $entry->event_type),
                'status' => 'recorded',
                'occurred_at' => $entry->occurred_at?->toAtomString(),
            ];
        }

        return $timeline;
    }

    /**
     * @return list<array{code: string, label: string, status: string, items: list<array<string, mixed>>}>
     */
    private function sections(
        int $quoteSubmissionCount,
        ?ComparisonRun $finalComparison,
        ?Approval $approval,
        ?Award $signedOffAward,
    ): array {
        return [
            [
                'code' => 'quote_sources',
                'label' => 'Quote sources',
                'status' => $quoteSubmissionCount > 0 ? 'complete' : 'missing',
                'items' => [
                    ['label' => 'Ready quote submissions', 'count' => $quoteSubmissionCount],
                ],
            ],
            [
                'code' => 'final_comparison',
                'label' => 'Final comparison',
                'status' => $finalComparison !== null ? 'complete' : 'missing',
                'items' => $finalComparison === null ? [] : [
                    ['id' => (string) $finalComparison->id, 'status' => $finalComparison->status],
                ],
            ],
            [
                'code' => 'approval_trail',
                'label' => 'Approval trail',
                'status' => $approval?->status === 'approved' ? 'complete' : 'missing',
                'items' => $approval === null ? [] : [
                    ['id' => (string) $approval->id, 'status' => $approval->status],
                ],
            ],
            [
                'code' => 'award_signoff',
                'label' => 'Award signoff',
                'status' => $signedOffAward !== null ? 'complete' : 'missing',
                'items' => $signedOffAward === null ? [] : [
                    ['id' => (string) $signedOffAward->id, 'status' => $signedOffAward->status],
                ],
            ],
        ];
    }

    private function label(string $code): string
    {
        return ucfirst(str_replace('_', ' ', $code));
    }
}
