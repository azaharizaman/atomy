'use client';

import { useNormalizationReview } from '@/hooks/use-normalization-review';
import { useRfqOverview } from '@/hooks/use-rfq-overview';

/**
 * Combines RFQ overview normalization counts with live conflict blocking metadata.
 */
export function useComparisonReadiness(rfqId: string, options?: { enabled?: boolean }) {
  const overview = useRfqOverview(rfqId);
  const norm = useNormalizationReview(rfqId, options);

  const total = overview.data?.normalization.total_quotes ?? 0;
  const ready =
    overview.data?.normalization.ready_count ?? overview.data?.normalization.accepted_count ?? 0;
  const allQuotesReady = total > 0 && ready >= total;

  return {
    overview,
    normalization: norm,
    hasBlockingIssues: norm.hasBlockingIssues,
    blockingIssueCount: norm.blockingIssueCount,
    allQuotesReady,
    canFreezeComparison: allQuotesReady && !norm.hasBlockingIssues,
  };
}
