import React from 'react';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import { screen } from '@testing-library/react';

import { renderWithProviders } from '@/test/utils';

let aiStatusData = {
  shouldHideAiControls: () => false,
  shouldShowUnavailableMessage: () => false,
  messageKeyForFeature: () => null,
  isFeatureAvailable: () => true,
};

const mockUseDashboardAiSummary = vi.fn();
const mockPush = vi.fn();
const mockFetchLiveOrFail = vi.fn();

vi.mock('next/navigation', () => ({
  useRouter: () => ({ push: mockPush }),
}));

vi.mock('@/lib/api-live', () => ({
  fetchLiveOrFail: (...args: unknown[]) => mockFetchLiveOrFail(...args),
}));

vi.mock('@/hooks/use-ai-status', () => ({
  useAiStatus: () => aiStatusData,
}));

vi.mock('@/hooks/use-dashboard-ai-summary', () => ({
  useDashboardAiSummary: (...args: unknown[]) => mockUseDashboardAiSummary(...args),
}));

import DashboardPage from './page';

describe('DashboardPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockFetchLiveOrFail.mockResolvedValue(undefined);
    aiStatusData = {
      shouldHideAiControls: () => false,
      shouldShowUnavailableMessage: () => false,
      messageKeyForFeature: () => null,
      isFeatureAvailable: () => true,
    };
  });

  it('renders the AI summary when available', () => {
    mockUseDashboardAiSummary.mockReturnValue({
      summary: {
        featureKey: 'insight_intelligence',
        available: true,
        headline: 'Dashboard health is steady.',
        summary: 'Factual signals remain within normal range.',
        bullets: ['Active RFQs are on track.', 'Approvals are within SLA.'],
        provenance: { provider: 'openrouter' },
        raw: null,
      },
      isLoading: false,
      isError: false,
      error: null,
    });

    renderWithProviders(<DashboardPage />);

    expect(screen.getByRole('heading', { name: 'Dashboard' })).toBeInTheDocument();
    expect(screen.getByText('Dashboard health is steady.')).toBeInTheDocument();
    expect(screen.getByText('Active RFQs are on track.')).toBeInTheDocument();
    expect(screen.getByText('AI-derived')).toBeInTheDocument();
  });

  it('renders an unavailable AI summary state without fabricating content', () => {
    aiStatusData = {
      shouldHideAiControls: () => false,
      shouldShowUnavailableMessage: () => true,
      messageKeyForFeature: () => 'ai.status.provider_unavailable',
      isFeatureAvailable: () => false,
    };
    mockUseDashboardAiSummary.mockReturnValue({
      summary: null,
      isLoading: false,
      isError: false,
      error: null,
    });

    renderWithProviders(<DashboardPage />);

    expect(screen.getByText('AI Summary unavailable')).toBeInTheDocument();
    expect(screen.queryByText('Dashboard health is steady.')).not.toBeInTheDocument();
  });

  it('renders KPI cards from the live API data wrapper', async () => {
    mockUseDashboardAiSummary.mockReturnValue({
      summary: null,
      isLoading: false,
      isError: false,
      error: null,
    });
    mockFetchLiveOrFail.mockImplementation((endpoint: string) => {
      if (endpoint === '/dashboard/kpis') {
        return Promise.resolve({
          data: {
            active_rfqs: 44,
            pending_approvals: 8,
            quote_intake_count: 40,
            awards_in_flight: 2,
            total_savings: 4308228.53,
          },
        });
      }

      return Promise.resolve({ data: [] });
    });

    renderWithProviders(<DashboardPage />);

    expect(await screen.findByText('44')).toBeInTheDocument();
    expect(screen.getByText('Active RFQs')).toBeInTheDocument();
    expect(screen.getByText('8')).toBeInTheDocument();
    expect(screen.getAllByText('Pending Approvals').length).toBeGreaterThan(0);
    expect(screen.getByText('40')).toBeInTheDocument();
    expect(screen.getByText('Quotes In Intake')).toBeInTheDocument();
    expect(screen.getByText('2')).toBeInTheDocument();
    expect(screen.getByText('Awards In Flight')).toBeInTheDocument();
    expect(screen.getByText('$4.3M')).toBeInTheDocument();
  });
});
