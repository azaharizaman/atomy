import React from 'react';
import { beforeAll, beforeEach, describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import { renderWithProviders } from '@/test/utils';

const mockUseNormalizationReview = vi.fn();
const mockUseNormalizationSourceLines = vi.fn();

beforeAll(() => {
  process.env.NEXT_PUBLIC_USE_MOCKS = 'false';
});

vi.mock('@/hooks/use-normalization-review', async (importOriginal) => {
  const actual = await importOriginal<typeof import('@/hooks/use-normalization-review')>();
  return {
    ...actual,
    useNormalizationReview: (...args: unknown[]) => mockUseNormalizationReview(...args),
  };
});

vi.mock('@/hooks/use-normalization-source-lines', () => ({
  useNormalizationSourceLines: (...args: unknown[]) => mockUseNormalizationSourceLines(...args),
}));

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
  beforeEach(() => {
    vi.clearAllMocks();

    mockUseNormalizationReview.mockReturnValue({
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
    });

    mockUseNormalizationSourceLines.mockReturnValue({
      data: [
        {
          id: 'line-1',
          quote_submission_id: 'q1',
          vendor_id: 'vendor-1',
          vendor_name: 'Vendor 1',
          source_description: 'Widget A',
          source_quantity: '2',
          source_uom: 'ea',
          source_unit_price: '',
          rfq_line_item_id: 'rfq-line-1',
          rfq_line_description: 'RFQ Widget A',
          rfq_line_quantity: '2',
          rfq_line_uom: 'ea',
          rfq_line_unit_price: '',
          sort_order: 0,
          confidence: 'high',
          conflict_count: 0,
          blocking_issue_count: 0,
          has_blocking_issue: false,
          quote_submission_status: 'ready',
        },
        {
          id: 'line-2',
          quote_submission_id: 'q1',
          vendor_id: 'vendor-2',
          vendor_name: 'Vendor 2',
          source_description: 'Widget B',
          source_quantity: '4',
          source_uom: 'ea',
          source_unit_price: '42',
          rfq_line_item_id: 'rfq-line-2',
          rfq_line_description: 'RFQ Widget B',
          rfq_line_quantity: '4',
          rfq_line_uom: 'ea',
          rfq_line_unit_price: '42',
          sort_order: 1,
          confidence: 'medium',
          conflict_count: 0,
          blocking_issue_count: 0,
          has_blocking_issue: false,
          quote_submission_status: 'ready',
        },
      ],
      isLoading: false,
      isError: false,
      error: null,
    });
  });

  it('shows blocking issues before allowing freeze', async () => {
    renderWithProviders(<NormalizePageContent rfqId="r1" quoteId="q1" />);

    expect(await screen.findByText(/blocking issues/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /freeze comparison/i })).toBeDisabled();
  });

  it('does not coerce empty unit prices to zero', async () => {
    renderWithProviders(<NormalizePageContent rfqId="r1" quoteId="q1" />);

    expect(await screen.findByText('Widget A')).toBeInTheDocument();
    expect(screen.getAllByText('$42').length).toBeGreaterThan(0);
    expect(screen.queryByText('$0')).not.toBeInTheDocument();
  });

  it('renders an explicit unavailable state when source lines fail to load in live mode', async () => {
    mockUseNormalizationSourceLines.mockReturnValue({
      data: [],
      isLoading: false,
      isError: true,
      error: new Error('Normalization source lines unavailable'),
    });

    renderWithProviders(<NormalizePageContent rfqId="r1" quoteId="q1" />);

    expect(await screen.findByText(/normalize workspace unavailable/i)).toBeInTheDocument();
    expect(screen.getByText(/normalization source lines unavailable/i)).toBeInTheDocument();
    expect(screen.queryByText(/no source lines yet/i)).not.toBeInTheDocument();
  });

  it('renders an explicit unavailable state when normalization review fails in live mode', async () => {
    mockUseNormalizationReview.mockReturnValue({
      conflicts: [],
      hasBlockingIssues: false,
      blockingIssueCount: 0,
      isLoading: false,
      isError: true,
      error: new Error('Normalization review unavailable'),
      data: undefined,
      status: 'error',
      fetchStatus: 'idle',
      resolveConflict: { mutate: vi.fn(), isPending: false, isError: false },
    });

    renderWithProviders(<NormalizePageContent rfqId="r1" quoteId="q1" />);

    expect(await screen.findByText(/normalize workspace unavailable/i)).toBeInTheDocument();
    expect(screen.getByText(/normalization review unavailable/i)).toBeInTheDocument();
    expect(screen.queryByText(/no source lines yet/i)).not.toBeInTheDocument();
  });
});
