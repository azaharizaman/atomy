<?php

declare(strict_types=1);

namespace App\Services\QuoteIntake;

use App\Models\QuoteSubmission;

final readonly class QuoteSubmissionReadinessService
{
    /**
     * @return array{has_blocking_issues: bool, blocking_issue_count: int, next_status: string}
     */
    public function evaluate(QuoteSubmission $submission): array
    {
        $submission->refresh();
        $submission->load(['normalizationSourceLines.conflicts', 'rfq.lineItems']);

        $blockingIssueCount = 0;

        $rfq = $submission->rfq;
        if ($rfq === null) {
            return [
                'has_blocking_issues' => true,
                'blocking_issue_count' => 1,
                'next_status' => 'needs_review',
            ];
        }

        $lineItemIds = $rfq->lineItems->pluck('id')->all();
        $sourceLines = $submission->normalizationSourceLines;

        $mappedRfqLineIds = $sourceLines
            ->pluck('rfq_line_item_id')
            ->filter()
            ->unique()
            ->all();

        foreach ($lineItemIds as $lineItemId) {
            if (! in_array($lineItemId, $mappedRfqLineIds, true)) {
                ++$blockingIssueCount;
            }
        }

        foreach ($sourceLines as $line) {
            if ($line->rfq_line_item_id === null) {
                continue;
            }
            if ($line->source_unit_price === null) {
                ++$blockingIssueCount;
            }
        }

        foreach ($sourceLines as $line) {
            foreach ($line->conflicts as $conflict) {
                if ($conflict->resolution === null) {
                    ++$blockingIssueCount;
                }
            }
        }

        return [
            'has_blocking_issues' => $blockingIssueCount > 0,
            'blocking_issue_count' => $blockingIssueCount,
            'next_status' => $blockingIssueCount > 0 ? 'needs_review' : 'ready',
        ];
    }
}
