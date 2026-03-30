import React from 'react';
import { beforeAll, describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import { renderWithProviders } from '@/test/utils';

beforeAll(() => {
  process.env.NEXT_PUBLIC_USE_MOCKS = 'false';
});

const mutate = vi.fn();
let awardState: unknown = null;

vi.mock('@/hooks/use-rfq', () => ({
  useRfq: () => ({ data: { title: 'Award RFQ' } }),
}));

vi.mock('@/hooks/use-rfq-vendors', () => ({
  useRfqVendors: () => ({
    data: [
      { id: 'vendor-1', name: 'Winner Vendor', status: 'responded', contact: 'winner@example.com' },
      { id: 'vendor-2', name: 'Other Vendor', status: 'invited', contact: 'other@example.com' },
    ],
    isLoading: false,
    isError: false,
  }),
}));

vi.mock('@/hooks/use-rfq-overview', () => ({
  useRfqOverview: () => ({
    data: {
      comparison: { id: 'run-1', status: 'final', is_preview: false, name: 'Final' },
    },
    isLoading: false,
    isError: false,
  }),
}));

vi.mock('@/hooks/use-award', () => ({
  useAward: () => ({
    data: awardState,
    isLoading: false,
    isError: false,
  }),
  useAwardRfq: () => ({
    mutate,
    isPending: false,
    isError: false,
  }),
}));

import { AwardPageContent } from './page';

describe('AwardPageContent', () => {
  it('shows the workflow action when no award has been recorded yet', async () => {
    awardState = null;
    renderWithProviders(<AwardPageContent rfqId="RFQ-1" />);

    expect(await screen.findByRole('button', { name: /award vendor/i })).toBeInTheDocument();
    expect(screen.getByLabelText(/winner vendor/i)).toBeInTheDocument();
  });

  it('shows the persisted award when the workflow has already created one', async () => {
    awardState = {
      id: 'award-1',
      rfq_id: 'RFQ-1',
      comparison_run_id: 'run-1',
      vendor_id: 'vendor-1',
      vendor_name: 'Winner Vendor',
      status: 'signed_off',
      amount: 900,
      currency: 'USD',
      signoff_at: '2026-03-31T10:00:00Z',
    };

    renderWithProviders(<AwardPageContent rfqId="RFQ-1" />);

    expect(await screen.findByText(/awarded to winner vendor/i)).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /award vendor/i })).not.toBeInTheDocument();
  });
});
