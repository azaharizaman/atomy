import React from 'react';
import { beforeAll, describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import { renderWithProviders } from '@/test/utils';

beforeAll(() => {
  process.env.NEXT_PUBLIC_USE_MOCKS = 'false';
});

vi.mock('next/navigation', () => ({
  useRouter: () => ({ push: vi.fn() }),
}));

vi.mock('@/hooks/use-rfq', () => ({
  useRfq: () => ({ data: { title: 'RFQ' } }),
}));

vi.mock('@/hooks/use-normalization-review', () => ({
  useNormalizationReview: () => ({
    hasBlockingIssues: true,
    blockingIssueCount: 2,
    conflicts: [],
  }),
}));

vi.mock('@/hooks/use-quote-submissions', () => ({
  useQuoteSubmissions: () => ({
    data: [
      {
        id: 'quote-1',
        rfq_id: 'rfq-1',
        vendor_id: 'vendor-1',
        vendor_name: 'Winner Vendor',
        file_name: 'winner.pdf',
        status: 'ready',
        confidence: 'high',
        uploaded_at: '2026-03-31T00:00:00Z',
        blocking_issue_count: 0,
      },
    ],
  }),
}));

import { QuoteIntakeListContent } from './page';

describe('QuoteIntakeListPage', () => {
  it('renders live quote rows and normalization warnings', async () => {
    renderWithProviders(<QuoteIntakeListContent rfqId="rfq-1" />);

    expect(await screen.findByText(/blocking issues/i)).toBeInTheDocument();
    expect(screen.getByText('winner.pdf')).toBeInTheDocument();
    expect(screen.getByText('Winner Vendor', { selector: 'span' })).toBeInTheDocument();
  });
});
