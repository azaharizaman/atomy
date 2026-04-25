import { Suspense } from 'react';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import { act, fireEvent, screen, waitFor } from '@testing-library/react';

import { renderWithProviders } from '@/test/utils';

const mockUseVendors = vi.fn();
const mockUseRfqVendors = vi.fn();
const mockUseRequisitionVendorSelection = vi.fn();
const mockUseUpdateRequisitionVendorSelection = vi.fn();
const mockUseVendorRecommendations = vi.fn();
const mockUseAiStatus = vi.fn();

vi.mock('@/hooks/use-rfq', () => ({
  useRfq: () => ({
    data: { title: 'RFQ' },
    isLoading: false,
    isError: false,
    error: null,
  }),
}));

vi.mock('@/hooks/use-vendors', () => ({
  useVendors: (...args: unknown[]) => mockUseVendors(...args),
}));

vi.mock('@/hooks/use-rfq-vendors', () => ({
  useRfqVendors: (...args: unknown[]) => mockUseRfqVendors(...args),
}));

vi.mock('@/hooks/use-requisition-vendor-selection', () => ({
  useRequisitionVendorSelection: (...args: unknown[]) => mockUseRequisitionVendorSelection(...args),
}));

vi.mock('@/hooks/use-update-requisition-vendor-selection', () => ({
  useUpdateRequisitionVendorSelection: (...args: unknown[]) => mockUseUpdateRequisitionVendorSelection(...args),
}));

vi.mock('@/hooks/use-vendor-recommendations', () => ({
  useVendorRecommendations: (...args: unknown[]) => mockUseVendorRecommendations(...args),
}));

vi.mock('@/hooks/use-ai-status', () => ({
  useAiStatus: () => mockUseAiStatus(),
}));

import RfqVendorsPage from './page';

async function renderRfqVendorsPage() {
  await act(async () => {
    renderWithProviders(
      <Suspense fallback={null}>
        <RfqVendorsPage params={Promise.resolve({ rfqId: 'rfq-1' })} />
      </Suspense>,
    );
  });
}

const alphaVendor = {
  id: 'vendor-1',
  legalName: 'Alpha Procurement Pte Ltd',
  displayName: 'Alpha Procurement',
  registrationNumber: 'REG-001',
  countryOfRegistration: 'SG',
  primaryContactName: 'Alicia Tan',
  primaryContactEmail: 'alicia@example.com',
  primaryContactPhone: '+65 9000 1111',
  status: 'approved',
  approvalRecord: null,
  createdAt: '2026-04-20T10:00:00Z',
  updatedAt: '2026-04-21T10:00:00Z',
  name: 'Alpha Procurement Pte Ltd',
  tradingName: 'Alpha Procurement',
  countryCode: 'SG',
  email: 'alicia@example.com',
  phone: '+65 9000 1111',
};

const betaVendor = {
  ...alphaVendor,
  id: 'vendor-2',
  displayName: 'Beta Supply',
  name: 'Beta Supply Pte Ltd',
  tradingName: 'Beta Supply',
  primaryContactEmail: 'beta@example.com',
  email: 'beta@example.com',
};

describe('vendor recommendations in RFQ vendor selection', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockUseAiStatus.mockReturnValue({
      isFeatureAvailable: () => true,
      shouldHideAiControls: () => false,
      shouldShowUnavailableMessage: () => false,
      messageKeyForFeature: () => null,
      status: { mode: 'provider', globalHealth: 'healthy', providerName: 'OpenRouter' },
    });
    mockUseVendors.mockReturnValue({
      data: {
        items: [alphaVendor, betaVendor],
        meta: { currentPage: 1, perPage: 25, total: 2, totalPages: 1 },
      },
      isLoading: false,
      isError: false,
      error: null,
    });
    mockUseRequisitionVendorSelection.mockReturnValue({
      data: [],
      isLoading: false,
      isError: false,
      error: null,
    });
    const eligibleCandidates = [
      {
        vendorId: 'vendor-1',
        vendorName: 'Alpha Procurement',
        fitScore: 88,
        confidenceBand: 'high',
        recommendedReasonSummary: 'Strong facilities category fit.',
        deterministicReasons: ['Category overlap: facilities.', 'Geography coverage matches SG.'],
        llmInsights: ['Narrative mentions emergency response, matching Alpha capabilities.'],
        warningFlags: ['sparse_historical_signal'],
        warnings: ['No recent activity signal available.'],
      },
    ];
    mockUseVendorRecommendations.mockReturnValue({
      data: {
        status: 'available',
        eligibleCandidates,
        excludedCandidates: [],
        providerExplanation: 'The AI ranking prioritizes facilities coverage and local response readiness.',
        deterministicReasonSet: ['Category overlap: facilities.', 'Geography coverage matches SG.'],
        provenance: { provider: 'openrouter', endpointGroup: 'vendor_ranking' },
        candidates: eligibleCandidates,
        excludedReasons: [],
      },
      isLoading: false,
      isError: false,
      error: null,
    });
    mockUseUpdateRequisitionVendorSelection.mockReturnValue({
      mutateAsync: vi.fn().mockResolvedValue(undefined),
      isPending: false,
      isError: false,
      error: null,
    });
    mockUseRfqVendors.mockReturnValue({
      data: [],
      isLoading: false,
      isError: false,
      error: null,
    });
  });

  it('shows recommendation details without auto-selecting them and still allows manual override', async () => {
    const saveMutation = vi.fn().mockResolvedValue(undefined);
    mockUseUpdateRequisitionVendorSelection.mockReturnValue({
      mutateAsync: saveMutation,
      isPending: false,
      isError: false,
      error: null,
    });

    await renderRfqVendorsPage();

    expect(screen.getByRole('checkbox', { name: /alpha procurement/i })).not.toBeChecked();
    expect(screen.getByText('Recommended')).toBeInTheDocument();
    expect(screen.getByText(/the ai ranking prioritizes facilities coverage/i)).toBeInTheDocument();

    fireEvent.click(screen.getByRole('button', { name: /why alpha procurement/i }));
    expect(screen.getByText('Strong facilities category fit.')).toBeInTheDocument();
    expect(screen.getByText('Category overlap: facilities.')).toBeInTheDocument();
    expect(screen.getByText('No recent activity signal available.')).toBeInTheDocument();

    fireEvent.click(screen.getByRole('checkbox', { name: /beta supply/i }));
    fireEvent.click(screen.getByRole('button', { name: /save selection/i }));

    await waitFor(() =>
      expect(saveMutation).toHaveBeenCalledWith({
        vendor_ids: ['vendor-2'],
      }),
    );
  });

  it('keeps manual selection usable and hides recommendation affordances when AI ranking is unavailable', async () => {
    const saveMutation = vi.fn().mockResolvedValue(undefined);
    mockUseAiStatus.mockReturnValue({
      isFeatureAvailable: () => false,
      shouldHideAiControls: () => true,
      shouldShowUnavailableMessage: (featureKey: string) => featureKey === 'vendor_ai_ranking',
      messageKeyForFeature: () => 'ai.status.unavailable',
      status: { mode: 'provider', globalHealth: 'degraded', providerName: 'OpenRouter' },
    });
    mockUseVendorRecommendations.mockReturnValue({
      data: {
        status: 'unavailable',
        eligibleCandidates: [],
        excludedCandidates: [],
        providerExplanation: 'AI recommendation is unavailable. You can still manually select vendors.',
        deterministicReasonSet: [],
        provenance: { code: 'ai_unavailable' },
        candidates: [],
        excludedReasons: [],
      },
      isLoading: false,
      isError: false,
      error: null,
    });
    mockUseUpdateRequisitionVendorSelection.mockReturnValue({
      mutateAsync: saveMutation,
      isPending: false,
      isError: false,
      error: null,
    });

    await renderRfqVendorsPage();

    expect(screen.getByText(/ai recommendation is unavailable/i)).toBeInTheDocument();
    expect(screen.getByText(/you can still manually select vendors/i)).toBeInTheDocument();
    expect(screen.queryByText('Recommended')).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /why alpha procurement/i })).not.toBeInTheDocument();

    fireEvent.click(screen.getByRole('checkbox', { name: /beta supply/i }));
    fireEvent.click(screen.getByRole('button', { name: /save selection/i }));

    await waitFor(() =>
      expect(saveMutation).toHaveBeenCalledWith({
        vendor_ids: ['vendor-2'],
      }),
    );
  });
});
