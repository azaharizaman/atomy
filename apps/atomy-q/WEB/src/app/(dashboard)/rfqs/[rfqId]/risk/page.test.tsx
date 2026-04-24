import React, { Suspense } from 'react';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import { act, screen } from '@testing-library/react';

import { renderWithProviders } from '@/test/utils';

let aiStatusData = {
  shouldHideAiControls: () => false,
  shouldShowUnavailableMessage: () => false,
  messageKeyForFeature: () => null,
  isFeatureAvailable: () => true,
};

const mockUseRfq = vi.fn();
const mockUseRfqAiSummary = vi.fn();

vi.mock('@/hooks/use-ai-status', () => ({
  useAiStatus: () => aiStatusData,
}));

vi.mock('@/hooks/use-rfq', () => ({
  useRfq: (...args: unknown[]) => mockUseRfq(...args),
}));

vi.mock('@/hooks/use-rfq-ai-summary', () => ({
  useRfqAiSummary: (...args: unknown[]) => mockUseRfqAiSummary(...args),
}));

import RiskPage from './page';

describe('RiskPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    aiStatusData = {
      shouldHideAiControls: () => false,
      shouldShowUnavailableMessage: () => false,
      messageKeyForFeature: () => null,
      isFeatureAvailable: () => true,
    };
    mockUseRfq.mockReturnValue({
      data: { id: 'rfq-1', title: 'Northwind Refresh' },
      isLoading: false,
      isError: false,
      error: null,
    });
  });

  it('renders the RFQ risk narrative and deterministic manual-review content', async () => {
    mockUseRfqAiSummary.mockReturnValue({
      summary: {
        featureKey: 'rfq_ai_insights',
        available: true,
        headline: 'Governance checks remain steady.',
        summary: 'No blocking issues surfaced in the latest review.',
        bullets: ['A single severe risk flag remains open.', 'Evidence freshness needs attention.'],
        provenance: { provider: 'openrouter' },
        raw: null,
      },
      isLoading: false,
      isError: false,
      error: null,
      isHidden: false,
      shouldShowUnavailableMessage: false,
      messageKey: null,
    });

    await act(async () => {
      renderWithProviders(
        <Suspense fallback={null}>
          <RiskPage params={Promise.resolve({ rfqId: 'rfq-1' })} />
        </Suspense>,
      );
    });

    expect(await screen.findByRole('heading', { name: 'Risk & Compliance' })).toBeInTheDocument();
    expect(mockUseRfqAiSummary).toHaveBeenCalledWith('rfq-1');
    expect(screen.getByText('Governance checks remain steady.')).toBeInTheDocument();
    expect(screen.getByText(/No persisted RFQ risk items are currently recorded\./i)).toBeInTheDocument();
    expect(screen.getByText(/Review vendor evidence and findings/i)).toBeInTheDocument();
  });
});
