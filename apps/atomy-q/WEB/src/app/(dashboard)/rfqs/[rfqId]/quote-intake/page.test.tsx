import React from 'react';
import { beforeAll, beforeEach, describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import { renderWithProviders } from '@/test/utils';

const mockUseNormalizationReview = vi.fn();
const mockUseQuoteSubmissions = vi.fn();
const mockUseAiStatus = vi.fn();

beforeAll(() => {
  process.env.NEXT_PUBLIC_USE_MOCKS = 'false';
});

vi.mock('next/navigation', () => ({
  useRouter: () => ({ push: vi.fn() }),
}));

vi.mock('@/hooks/use-rfq', () => ({
  useRfq: () => ({ data: { title: 'RFQ' } }),
}));

vi.mock('@/hooks/use-normalization-review', () => ({
  useNormalizationReview: (...args: unknown[]) => mockUseNormalizationReview(...args),
}));

vi.mock('@/hooks/use-quote-submissions', () => ({
  useQuoteSubmissions: (...args: unknown[]) => mockUseQuoteSubmissions(...args),
}));

vi.mock('@/hooks/use-ai-status', () => ({
  useAiStatus: () => mockUseAiStatus(),
}));

import { QuoteIntakeListContent } from './page';

describe('QuoteIntakeListPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();

    mockUseNormalizationReview.mockReturnValue({
      hasBlockingIssues: true,
      blockingIssueCount: 2,
      conflicts: [],
      isLoading: false,
      isError: false,
      error: null,
    });

    mockUseQuoteSubmissions.mockReturnValue({
      data: [
        {
          id: 'quote-1',
          rfq_id: 'rfq-1',
          vendor_id: 'vendor-1',
          vendor_name: 'Winner Vendor',
          file_name: 'winner.pdf',
          status: 'ready',
          confidence: 'high',
          uploaded_at: '2026-03-31T00:00:00Z',
          blocking_issue_count: 0,
          extraction_origin: 'provider',
          provider_name: 'OpenRouter',
        },
      ],
      isLoading: false,
      isError: false,
      error: null,
    });

    mockUseAiStatus.mockReturnValue({
      isFeatureAvailable: (featureKey: string) => featureKey === 'quote_document_extraction',
      shouldHideAiControls: () => false,
      shouldShowUnavailableMessage: () => false,
      messageKeyForFeature: () => null,
      status: { mode: 'provider', globalHealth: 'healthy', providerName: 'OpenRouter' },
    });
  });

  it('renders live quote rows and normalization warnings', async () => {
    renderWithProviders(<QuoteIntakeListContent rfqId="rfq-1" />);

    expect(await screen.findByText(/blocking issues/i)).toBeInTheDocument();
    expect(screen.getByText('winner.pdf')).toBeInTheDocument();
    expect(screen.getByText('Winner Vendor', { selector: 'span' })).toBeInTheDocument();
  });

  it('makes provider-backed quote extraction visible when AI extraction is available', async () => {
    renderWithProviders(<QuoteIntakeListContent rfqId="rfq-1" />);

    expect(await screen.findByText(/ai-assisted quote extraction/i)).toBeInTheDocument();
    expect(screen.getByText(/provider extraction active/i)).toBeInTheDocument();
    expect(screen.getByText(/OpenRouter/i)).toBeInTheDocument();
  });

  it('keeps quote intake usable and scopes messaging when extraction AI is unavailable', async () => {
    mockUseAiStatus.mockReturnValue({
      isFeatureAvailable: () => false,
      shouldHideAiControls: () => true,
      shouldShowUnavailableMessage: (featureKey: string) => featureKey === 'quote_document_extraction',
      messageKeyForFeature: () => 'ai.status.unavailable',
      status: { mode: 'provider', globalHealth: 'degraded', providerName: null },
    });

    renderWithProviders(<QuoteIntakeListContent rfqId="rfq-1" />);

    expect(await screen.findByText('winner.pdf')).toBeInTheDocument();
    expect(screen.getByText(/ai extraction is unavailable/i)).toBeInTheDocument();
    expect(screen.getByText(/continue by entering source lines manually/i)).toBeInTheDocument();
    expect(screen.queryByText(/quote intake unavailable/i)).not.toBeInTheDocument();
  });

  it('renders an explicit unavailable state when quote submissions fail to load', async () => {
    mockUseQuoteSubmissions.mockReturnValue({
      data: [],
      isLoading: false,
      isError: true,
      error: new Error('Quote submissions unavailable'),
    });

    renderWithProviders(<QuoteIntakeListContent rfqId="rfq-1" />);

    expect(await screen.findByText(/quote intake unavailable/i)).toBeInTheDocument();
    expect(screen.getByText(/quote submissions unavailable/i)).toBeInTheDocument();
    expect(screen.queryByText(/no submissions yet/i)).not.toBeInTheDocument();
  });

  it('renders an explicit unavailable state when normalization review fails in live mode', async () => {
    mockUseNormalizationReview.mockReturnValue({
      hasBlockingIssues: false,
      blockingIssueCount: 0,
      conflicts: [],
      isLoading: false,
      isError: true,
      error: new Error('Normalization review unavailable'),
    });

    renderWithProviders(<QuoteIntakeListContent rfqId="rfq-1" />);

    expect(await screen.findByText('winner.pdf')).toBeInTheDocument();
    expect(screen.getByText(/normalization review unavailable/i)).toBeInTheDocument();
    expect(screen.queryByText(/quote intake unavailable/i)).not.toBeInTheDocument();
  });
});
