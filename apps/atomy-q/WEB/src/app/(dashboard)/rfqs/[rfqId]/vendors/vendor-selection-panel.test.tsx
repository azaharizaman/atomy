import { Suspense } from 'react';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import { act, fireEvent, screen, waitFor } from '@testing-library/react';

import { renderWithProviders } from '@/test/utils';

const mockUseVendors = vi.fn();
const mockUseRfqVendors = vi.fn();
const mockUseRequisitionVendorSelection = vi.fn();
const mockUseUpdateRequisitionVendorSelection = vi.fn();
const mockUseVendorGovernanceMap = vi.fn();
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

vi.mock('@/hooks/use-vendor-governance', async () => {
  const actual = await vi.importActual<typeof import('@/hooks/use-vendor-governance')>('@/hooks/use-vendor-governance');
  return {
    ...actual,
    useVendorGovernanceMap: (...args: unknown[]) => mockUseVendorGovernanceMap(...args),
  };
});

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

const approvedVendor = {
  id: 'vendor-1',
  legalName: 'Alpha Procurement Pte Ltd',
  displayName: 'Alpha Procurement',
  registrationNumber: 'REG-001',
  countryOfRegistration: 'SG',
  primaryContactName: 'Alicia Tan',
  primaryContactEmail: 'alicia@example.com',
  primaryContactPhone: '+65 9000 1111',
  status: 'approved',
  approvalRecord: {
    approvedByUserId: 'user-1',
    approvedAt: '2026-04-22T10:00:00Z',
    approvalNote: 'Approved for RFQ sourcing',
  },
  createdAt: '2026-04-20T10:00:00Z',
  updatedAt: '2026-04-21T10:00:00Z',
  name: 'Alpha Procurement Pte Ltd',
  tradingName: 'Alpha Procurement',
  countryCode: 'SG',
  email: 'alicia@example.com',
  phone: '+65 9000 1111',
};

const secondApprovedVendor = {
  ...approvedVendor,
  id: 'vendor-2',
  displayName: 'Beta Supply',
  name: 'Beta Supply Pte Ltd',
  tradingName: 'Beta Supply',
  primaryContactEmail: 'beta@example.com',
  email: 'beta@example.com',
};

const restrictedVendor = {
  ...approvedVendor,
  id: 'vendor-3',
  displayName: 'Gamma Restricted',
  name: 'Gamma Restricted Pte Ltd',
  tradingName: 'Gamma Restricted',
  status: 'restricted' as const,
  primaryContactEmail: 'gamma@example.com',
  email: 'gamma@example.com',
};

describe('RfqVendorSelectionPanel', () => {
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
        items: [approvedVendor, secondApprovedVendor],
        meta: { currentPage: 1, perPage: 25, total: 2, totalPages: 1 },
      },
      isLoading: false,
      isError: false,
      error: null,
    });
    mockUseRequisitionVendorSelection.mockReturnValue({
      data: [
        {
          id: 'sel-1',
          rfqId: 'rfq-1',
          vendorId: 'vendor-1',
          vendorName: 'Alpha Procurement',
          vendorDisplayName: 'Alpha Procurement',
          vendorEmail: 'alicia@example.com',
          status: 'approved',
          selectedAt: '2026-04-22T10:00:00Z',
          selectedByUserId: 'user-1',
        },
      ],
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
    mockUseVendorRecommendations.mockReturnValue({
      data: {
        status: 'available',
        eligibleCandidates: [],
        excludedCandidates: [],
        providerExplanation: null,
        deterministicReasonSet: [],
        provenance: null,
        candidates: [],
        excludedReasons: [],
      },
      isLoading: false,
      isError: false,
      error: null,
    });
    mockUseVendorGovernanceMap.mockReturnValue({
      data: new Map(),
      isLoading: false,
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

  it('lists approved vendors only, keeps selected state, and saves selected ids', async () => {
    const saveMutation = vi.fn().mockResolvedValue(undefined);
    mockUseUpdateRequisitionVendorSelection.mockReturnValue({
      mutateAsync: saveMutation,
      isPending: false,
      isError: false,
      error: null,
    });
    mockUseVendors.mockImplementation((filters: { status?: string; q?: string } = {}) => {
      const allVendors = [approvedVendor, secondApprovedVendor, restrictedVendor];
      let items = filters.status === 'approved'
        ? allVendors.filter((vendor) => vendor.status === 'approved')
        : allVendors;

      if (filters.q === 'beta') {
        items = items.filter((vendor) => vendor.displayName.toLowerCase().includes('beta'));
      }

      return {
        data: {
          items,
          meta: { currentPage: 1, perPage: 25, total: items.length, totalPages: 1 },
        },
        isLoading: false,
        isError: false,
        error: null,
      };
    });

    await renderRfqVendorsPage();

    expect(screen.getAllByText('Alpha Procurement').length).toBeGreaterThan(0);
    expect(screen.getByText('Beta Supply')).toBeInTheDocument();
    expect(screen.queryByText('Gamma Restricted')).not.toBeInTheDocument();
    expect(screen.getByRole('checkbox', { name: /alpha procurement/i })).toBeChecked();
    expect(screen.getByRole('checkbox', { name: /beta supply/i })).not.toBeChecked();

    fireEvent.change(screen.getByLabelText(/search approved vendors/i), { target: { value: 'beta' } });

    await waitFor(() => expect(mockUseVendors).toHaveBeenCalledWith(expect.objectContaining({ q: 'beta' })));
    expect(screen.queryByRole('checkbox', { name: /alpha procurement/i })).not.toBeInTheDocument();
    expect(screen.getByText('Beta Supply')).toBeInTheDocument();

    fireEvent.click(screen.getByRole('checkbox', { name: /beta supply/i }));
    fireEvent.click(screen.getByRole('button', { name: /save selection/i }));

    await waitFor(() =>
      expect(saveMutation).toHaveBeenCalledWith({
        vendor_ids: ['vendor-1', 'vendor-2'],
      }),
    );
  });

  it('shows an empty state that points to the vendor master when no approved vendors exist', async () => {
    mockUseVendors.mockReturnValue({
      data: { items: [], meta: { currentPage: 1, perPage: 25, total: 0, totalPages: 1 } },
      isLoading: false,
      isError: false,
      error: null,
    });

    await renderRfqVendorsPage();

    expect(screen.getByText(/no approved vendors available/i)).toBeInTheDocument();
    expect(screen.getByRole('link', { name: /browse vendors/i })).toHaveAttribute('href', '/vendors');
  });

  it('surfaces stale governance evidence warnings without blocking selection', async () => {
    const saveMutation = vi.fn().mockResolvedValue(undefined);
    mockUseUpdateRequisitionVendorSelection.mockReturnValue({
      mutateAsync: saveMutation,
      isPending: false,
      isError: false,
      error: null,
    });
    mockUseVendors.mockReturnValue({
      data: {
        items: [approvedVendor],
        meta: { currentPage: 1, perPage: 25, total: 1, totalPages: 1 },
      },
      isLoading: false,
      isError: false,
      error: null,
    });
    mockUseVendorGovernanceMap.mockReturnValue({
      data: new Map([
        [
          'vendor-1',
          {
            vendorId: 'vendor-1',
            evidence: [],
            findings: [],
            scores: {
              esgScore: 65,
              complianceHealthScore: 55,
              riskWatchScore: 100,
              evidenceFreshnessScore: 40,
            },
            warningFlags: ['stale_evidence'],
          },
        ],
      ]),
      isLoading: false,
      isError: false,
      error: null,
    });

    await renderRfqVendorsPage();

    expect(screen.getByText('Stale Evidence')).toBeInTheDocument();
    fireEvent.click(screen.getByRole('button', { name: /save selection/i }));

    await waitFor(() =>
      expect(saveMutation).toHaveBeenCalledWith({
        vendor_ids: ['vendor-1'],
      }),
    );
  });

  it('keeps manual selection usable while hiding recommendation affordances when AI ranking is unavailable', async () => {
    const saveMutation = vi.fn().mockResolvedValue(undefined);
    mockUseAiStatus.mockReturnValue({
      isFeatureAvailable: () => false,
      shouldHideAiControls: () => true,
      shouldShowUnavailableMessage: (featureKey: string) => featureKey === 'vendor_ai_ranking',
      messageKeyForFeature: () => 'ai.status.unavailable',
      status: { mode: 'provider', globalHealth: 'degraded', providerName: 'OpenRouter' },
    });
    mockUseRequisitionVendorSelection.mockReturnValue({
      data: [],
      isLoading: false,
      isError: false,
      error: null,
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

    fireEvent.click(screen.getByRole('checkbox', { name: /alpha procurement/i }));
    fireEvent.click(screen.getByRole('button', { name: /save selection/i }));

    await waitFor(() =>
      expect(saveMutation).toHaveBeenCalledWith({
        vendor_ids: ['vendor-1'],
      }),
    );
  });
});
