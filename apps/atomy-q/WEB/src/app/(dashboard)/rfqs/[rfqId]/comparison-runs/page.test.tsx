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
          date: 'Today',
          type: 'final' as const,
          status: 'locked',
          name: 'Final comparison',
          created_at: '2026-04-06T08:00:00Z',
        },
      ],
    });

    renderWithProviders(<ComparisonRunsListContent rfqId="RFQ-2026-0001" />);

    expect(await screen.findByText(/snapshot frozen/i)).toBeInTheDocument();
    expect(screen.getByRole('link', { name: /decision trail/i })).toBeInTheDocument();
    expect(screen.getByText('Final comparison')).toBeInTheDocument();
    expect(screen.getByText('run-1')).toBeInTheDocument();
  });

  it('hides the snapshot banner when there are no final runs', async () => {
    mockUseComparisonRuns.mockReturnValue({
      data: [
        {
          id: 'run-preview-1',
          rfq_id: 'rfq-1',
          date: 'Today',
          type: 'preview' as const,
          status: 'preview',
          name: 'Preview comparison',
          created_at: '2026-04-06T08:00:00Z',
        },
      ],
    });

    renderWithProviders(<ComparisonRunsListContent rfqId="RFQ-2026-0001" />);

    expect(await screen.findByText('Preview comparison')).toBeInTheDocument();
    expect(screen.getByText('run-preview-1')).toBeInTheDocument();
    expect(screen.queryByText(/snapshot frozen/i)).not.toBeInTheDocument();
    expect(screen.queryByRole('link', { name: /decision trail/i })).not.toBeInTheDocument();
  });

  it('renders an explicit unavailable state when comparison runs fail to load', async () => {
    mockUseComparisonRuns.mockReturnValue({
      data: [],
      isLoading: false,
      isError: true,
      error: new Error('Comparison runs unavailable'),
    });

    renderWithProviders(<ComparisonRunsListContent rfqId="RFQ-2026-0001" />);

    expect(await screen.findByText(/could not load comparison runs/i)).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'Comparison runs unavailable' })).toBeInTheDocument();
    expect(screen.queryByText(/snapshot frozen/i)).not.toBeInTheDocument();
  });
});
