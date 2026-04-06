import React from 'react';
import { beforeAll, beforeEach, describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import { renderWithProviders } from '@/test/utils';

const mockUseComparisonRuns = vi.fn();

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
  useComparisonRuns: (...args: unknown[]) => mockUseComparisonRuns(...args),
}));

import { ComparisonRunsListContent } from './page';

describe('ComparisonRunsPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('shows snapshot frozen status from the live run hook', async () => {
    mockUseComparisonRuns.mockReturnValue({
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
    });

    renderWithProviders(<ComparisonRunsListContent rfqId="RFQ-2026-0001" />);

    expect(await screen.findByText(/snapshot frozen/i)).toBeInTheDocument();
    expect(screen.getByRole('link', { name: /decision trail/i })).toBeInTheDocument();
    expect(screen.getByText('RUN-9001')).toBeInTheDocument();
  });

  it('hides the snapshot banner when there are no final runs', async () => {
    mockUseComparisonRuns.mockReturnValue({
      data: [
        {
          id: 'run-preview-1',
          rfq_id: 'rfq-1',
          run_id: 'RUN-PREVIEW-1',
          date: 'Today',
          type: 'preview' as const,
          status: 'preview',
          scoring_model: 'v1',
          created_by: 'Alex Kumar',
        },
      ],
    });

    renderWithProviders(<ComparisonRunsListContent rfqId="RFQ-2026-0001" />);

    expect(await screen.findByText('RUN-PREVIEW-1')).toBeInTheDocument();
    expect(screen.queryByText(/snapshot frozen/i)).not.toBeInTheDocument();
    expect(screen.queryByRole('link', { name: /decision trail/i })).not.toBeInTheDocument();
  });
});
