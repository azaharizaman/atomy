import React, { Suspense } from 'react';
import { act, screen, within } from '@testing-library/react';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import { renderWithProviders } from '@/test/utils';

import RfqOverviewPage from './page';

const mockUseRfqOverview = vi.fn();
const mockUseRfqAiSummary = vi.fn();

vi.mock('@/hooks/use-rfq-overview', () => ({
  useRfqOverview: (...args: unknown[]) => mockUseRfqOverview(...args),
}));

vi.mock('@/hooks/use-rfq-ai-summary', () => ({
  useRfqAiSummary: (...args: unknown[]) => mockUseRfqAiSummary(...args),
}));

describe('RfqOverviewPage', () => {
  beforeEach(() => {
    mockUseRfqOverview.mockReset();
    mockUseRfqAiSummary.mockReset();
    mockUseRfqAiSummary.mockReturnValue({
      summary: null,
      isLoading: false,
      isError: false,
      error: null,
      isHidden: false,
      shouldShowUnavailableMessage: false,
      messageKey: null,
    });
  });

  it('renders the generic unavailable state when overview data is missing', async () => {
    mockUseRfqOverview.mockReturnValue({
      data: undefined,
      isLoading: false,
      isError: false,
      error: undefined,
    });

    await act(async () => {
      renderWithProviders(
        <Suspense fallback={null}>
          <RfqOverviewPage params={Promise.resolve({ rfqId: 'rfq-missing-1' })} />
        </Suspense>,
      );
    });

    expect(await screen.findByRole('heading', { name: 'Overview' })).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'Unable to load overview' })).toBeInTheDocument();
    expect(screen.getByText(/could not load this rfq's overview from the live api/i)).toBeInTheDocument();
    expect(screen.getByText(/we could not load the overview for this rfq right now/i)).toBeInTheDocument();
  });

  it('renders the happy-path overview content', async () => {
    mockUseRfqOverview.mockReturnValue({
      data: {
        rfq: {
          id: 'rfq-1',
          rfq_number: 'RFQ-0001',
          title: 'Northwind Refresh',
          status: 'active',
          submission_deadline: '2026-04-30T10:00:00Z',
          closing_date: '2026-04-30T12:00:00Z',
          expected_award_at: '2026-05-05T10:00:00Z',
          technical_review_due_at: '2026-04-26T10:00:00Z',
          financial_review_due_at: '2026-04-28T10:00:00Z',
          vendors_count: 4,
          quotes_count: 2,
        },
        expected_quotes: 4,
        normalization: {
          accepted_count: 2,
          total_quotes: 2,
          progress_pct: 100,
          uploaded_count: 2,
          needs_review_count: 0,
          ready_count: 2,
        },
        comparison: null,
        approvals: {
          pending_count: 0,
          approved_count: 0,
          rejected_count: 0,
          overall: 'none',
        },
        activity: [],
      },
      isLoading: false,
      isError: false,
      error: undefined,
    });

    await act(async () => {
      renderWithProviders(
        <Suspense fallback={null}>
          <RfqOverviewPage params={Promise.resolve({ rfqId: 'rfq-1' })} />
        </Suspense>,
      );
    });

    expect(screen.getByText('Next step')).toBeInTheDocument();
    expect(screen.getByText('Schedule')).toBeInTheDocument();
    expect(screen.getByText('Quotes received')).toBeInTheDocument();
    expect(screen.getByText('Pending approvals')).toBeInTheDocument();
    expect(screen.getByText('Activity timeline')).toBeInTheDocument();
    expect(screen.getByText(/No activity yet\./i)).toBeInTheDocument();
  });

  it('uses numeric primary values and equal-height wrappers for RFQ KPI cards', async () => {
    mockUseRfqOverview.mockReturnValue({
      data: {
        rfq: {
          id: 'rfq-2',
          rfq_number: 'RFQ-0002',
          title: 'Compressor Package',
          status: 'active',
          submission_deadline: '2026-04-30T10:00:00Z',
          vendors_count: 8,
          quotes_count: 6,
        },
        expected_quotes: 8,
        normalization: {
          accepted_count: 3,
          total_quotes: 6,
          progress_pct: 50,
          uploaded_count: 6,
          needs_review_count: 3,
          ready_count: 3,
        },
        comparison_runs_count: 3,
        comparison: {
          id: 'run-3',
          name: 'Final comparison',
          status: 'locked',
          is_preview: false,
          created_at: '2026-04-29T10:00:00Z',
        },
        approvals: {
          pending_count: 2,
          approved_count: 1,
          rejected_count: 0,
          overall: 'pending',
        },
        activity: [],
      },
      isLoading: false,
      isError: false,
      error: undefined,
    });

    await act(async () => {
      renderWithProviders(
        <Suspense fallback={null}>
          <RfqOverviewPage params={Promise.resolve({ rfqId: 'rfq-2' })} />
        </Suspense>,
      );
    });

    const comparisonLink = screen.getByRole('link', { name: /comparison runs/i });
    expect(comparisonLink).toHaveClass('h-full');
    expect(within(comparisonLink).getByText('3')).toBeInTheDocument();
    expect(within(comparisonLink).getByText('Latest run available')).toBeInTheDocument();
    expect(within(comparisonLink).getByText('locked')).toBeInTheDocument();

    const approvalLink = screen.getByRole('link', { name: /pending approvals/i });
    expect(approvalLink).toHaveClass('h-full');
    expect(within(approvalLink).getByText('2')).toBeInTheDocument();
    expect(within(approvalLink).getByText('Action required')).toBeInTheDocument();
  });
});
