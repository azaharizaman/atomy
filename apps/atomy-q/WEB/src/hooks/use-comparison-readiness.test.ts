import { describe, expect, it, vi } from 'vitest';
import { renderHook } from '@testing-library/react';
import { createTestWrapper } from '@/test/utils';
import { useComparisonReadiness } from './use-comparison-readiness';

vi.mock('@/hooks/use-rfq-overview', () => ({
  useRfqOverview: () => ({
    data: {
      normalization: {
        total_quotes: 2,
        ready_count: 2,
        accepted_count: 2,
      },
    },
  }),
}));

vi.mock('@/hooks/use-normalization-review', () => ({
  useNormalizationReview: () => ({
    hasBlockingIssues: true,
    blockingIssueCount: 1,
  }),
}));

describe('useComparisonReadiness', () => {
  it('blocks freeze when live conflicts remain', () => {
    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useComparisonReadiness('rfq-1'), { wrapper: Wrapper });

    expect(result.current.allQuotesReady).toBe(true);
    expect(result.current.canFreezeComparison).toBe(false);
    expect(result.current.blockingIssueCount).toBe(1);
  });
});
