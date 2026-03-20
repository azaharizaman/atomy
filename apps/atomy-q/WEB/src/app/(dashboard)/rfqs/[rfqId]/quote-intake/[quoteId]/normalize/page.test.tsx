import React from 'react';
import { describe, expect, it, vi, beforeAll } from 'vitest';
import { screen } from '@testing-library/react';
import { renderWithProviders } from '@/test/utils';

beforeAll(() => {
  process.env.NEXT_PUBLIC_USE_MOCKS = 'false';
});

vi.mock('@/hooks/use-normalization-review', async (importOriginal) => {
  const actual = await importOriginal<typeof import('@/hooks/use-normalization-review')>();
  return {
    ...actual,
    useNormalizationReview: () => ({
      conflicts: [],
      hasBlockingIssues: true,
      blockingIssueCount: 2,
      isLoading: false,
      isError: false,
      error: null,
      data: undefined,
      status: 'success',
      fetchStatus: 'idle',
      resolveConflict: { mutate: vi.fn(), isPending: false, isError: false },
    }),
  };
});

vi.mock('@/hooks/use-freeze-comparison', () => ({
  useFreezeComparison: () => ({
    mutate: vi.fn(),
    isPending: false,
    isError: false,
  }),
}));

vi.mock('@/hooks/use-rfq', () => ({
  useRfq: () => ({ data: { title: 'RFQ' } }),
}));

import { NormalizePageContent } from './page';

describe('NormalizeQuotePage', () => {
  it('shows blocking issues before allowing freeze', async () => {
    renderWithProviders(<NormalizePageContent rfqId="r1" quoteId="q1" />);

    expect(await screen.findByText(/blocking issues/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /freeze comparison/i })).toBeDisabled();
  });
});
