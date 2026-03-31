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

vi.mock('@/hooks/use-comparison-runs', () => ({
  useComparisonRuns: () => ({
    data: [
      {
        id: 'run-1',
        rfq_id: 'rfq-1',
        run_id: 'RUN-9001',
        date: 'Today',
        type: 'final' as const,
        status: 'locked',
        scoring_model: 'v2.0',
        created_by: 'Alex Kumar',
      },
    ],
  }),
}));

import { ComparisonRunsListContent } from './page';

describe('ComparisonRunsPage', () => {
  it('shows snapshot frozen status from the live run hook', async () => {
    renderWithProviders(<ComparisonRunsListContent rfqId="RFQ-2026-0001" />);

    expect(await screen.findByText(/snapshot frozen/i)).toBeInTheDocument();
    expect(screen.getByRole('link', { name: /decision trail/i })).toBeInTheDocument();
    expect(screen.getByText('RUN-9001')).toBeInTheDocument();
  });
});
