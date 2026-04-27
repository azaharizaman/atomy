import React from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import { renderWithProviders } from '@/test/utils';

const mockUseRfq = vi.fn();
const mockUseRfqOverview = vi.fn();
const mockUseRfqAiSummary = vi.fn();

vi.mock('next/link', () => ({
  default: ({ children }: { children: React.ReactNode }) => <>{children}</>,
}));

vi.mock('@/hooks/use-rfq', () => ({
  useRfq: (...args: unknown[]) => mockUseRfq(...args),
}));

vi.mock('@/hooks/use-rfq-overview', () => ({
  useRfqOverview: (...args: unknown[]) => mockUseRfqOverview(...args),
}));

vi.mock('@/hooks/use-rfq-ai-summary', () => ({
  useRfqAiSummary: (...args: unknown[]) => mockUseRfqAiSummary(...args),
}));

vi.mock('@/components/ai/ai-narrative-panel', () => ({
  AiNarrativePanel: () => <div data-testid="ai-narrative-panel" />,
}));

import { RfqInsightsSidebar } from './rfq-insights-sidebar';

describe('RfqInsightsSidebar', () => {
  beforeEach(() => {
    vi.clearAllMocks();
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

  it('shows plain-language copy when the RFQ is not accessible', () => {
    mockUseRfq.mockReturnValue({
      data: undefined,
      isLoading: false,
      isError: true,
      error: { status: 404 },
    });
    mockUseRfqOverview.mockReturnValue({
      data: undefined,
      isLoading: false,
      isError: false,
      error: null,
    });

    renderWithProviders(<RfqInsightsSidebar rfqId="rfq-1" />);

    expect(screen.getByText('Insights unavailable')).toBeInTheDocument();
    expect(screen.getByText(/not available in your workspace, so insights cannot be shown/i)).toBeInTheDocument();
  });

  it('shows plain-language copy when the overview payload is incomplete', () => {
    mockUseRfq.mockReturnValue({
      data: { status: 'active', quotesCount: 0 },
      isLoading: false,
      isError: false,
      error: null,
    });
    mockUseRfqOverview.mockReturnValue({
      data: undefined,
      isLoading: false,
      isError: true,
      error: new Error('RFQ overview payload is incomplete.'),
    });

    renderWithProviders(<RfqInsightsSidebar rfqId="rfq-1" />);

    expect(screen.getByText('Insights unavailable')).toBeInTheDocument();
    expect(screen.getByText(/the data needed for insights is incomplete/i)).toBeInTheDocument();
  });
});
