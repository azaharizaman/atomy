import React from 'react';
import { describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import { renderWithProviders } from '@/test/utils';

vi.hoisted(() => {
  process.env.NEXT_PUBLIC_USE_MOCKS = 'true';
});

vi.mock('next/navigation', () => ({
  useRouter: () => ({ push: vi.fn() }),
}));

vi.mock('@/hooks/use-rfq', () => ({
  useRfq: () => ({ data: { title: 'RFQ' } }),
}));

vi.mock('@/data/seed', () => ({
  getSeedComparisonRunsByRfqId: () => [
    {
      id: 'run-1',
      runId: 'RUN-9001',
      date: 'Today',
      type: 'final' as const,
      status: 'locked' as const,
      scoringModel: 'v2.0',
      createdBy: 'Alex Kumar',
    },
  ],
}));

import { ComparisonRunsListContent } from './page';

describe('ComparisonRunsPage', () => {
  it('shows snapshot frozen status and decision trail link', async () => {
    renderWithProviders(<ComparisonRunsListContent rfqId="RFQ-2026-0001" />);

    expect(await screen.findByText(/snapshot frozen/i)).toBeInTheDocument();
    expect(screen.getByRole('link', { name: /decision trail/i })).toBeInTheDocument();
  });
});
