import { beforeAll, beforeEach, describe, expect, it, vi } from 'vitest';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import { renderWithProviders } from '@/test/utils';

const mockUseRfqVendors = vi.fn();
const mockUseVendors = vi.fn();
const mockUseRequisitionVendorSelection = vi.fn();
const mockUseUpdateRequisitionVendorSelection = vi.fn();
const mockUseInviteSelectedVendors = vi.fn();
const mockUseVendorGovernanceMap = vi.fn();
const mockUseVendorRecommendations = vi.fn();
const mockUseAiStatus = vi.fn();

beforeAll(() => {
  process.env.NEXT_PUBLIC_USE_MOCKS = 'false';
});

vi.mock('@/hooks/use-rfq', () => ({
  useRfq: () => ({
    data: { title: 'RFQ' },
    isLoading: false,
    isError: false,
    error: null,
  }),
}));

vi.mock('@/hooks/use-rfq-vendors', () => ({
  useRfqVendors: (...args: unknown[]) => mockUseRfqVendors(...args),
}));

vi.mock('@/hooks/use-vendors', () => ({
  useVendors: (...args: unknown[]) => mockUseVendors(...args),
}));

vi.mock('@/hooks/use-requisition-vendor-selection', () => ({
  useRequisitionVendorSelection: (...args: unknown[]) => mockUseRequisitionVendorSelection(...args),
}));

vi.mock('@/hooks/use-update-requisition-vendor-selection', () => ({
  useUpdateRequisitionVendorSelection: (...args: unknown[]) => mockUseUpdateRequisitionVendorSelection(...args),
}));

vi.mock('@/hooks/use-invite-selected-vendors', () => ({
  useInviteSelectedVendors: (...args: unknown[]) => mockUseInviteSelectedVendors(...args),
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

import { RfqVendorsPageContent } from './page';

describe('RfqVendorsPage', () => {
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
      data: { items: [], meta: { currentPage: 1, perPage: 25, total: 0, totalPages: 1 } },
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
    mockUseUpdateRequisitionVendorSelection.mockReturnValue({
      mutateAsync: vi.fn(),
      isPending: false,
      isError: false,
      error: null,
    });
    mockUseInviteSelectedVendors.mockReturnValue({
      mutateAsync: vi.fn(),
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

  it('renders an explicit unavailable state when vendor data fails to load', async () => {
    mockUseRfqVendors.mockReturnValue({
      data: [],
      isLoading: false,
      isError: true,
      error: new Error('Live vendor roster unavailable'),
    });
    renderWithProviders(<RfqVendorsPageContent rfqId="rfq-1" />);

    expect(await screen.findByText(/could not load invited vendors/i)).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'Vendor roster unavailable' })).toBeInTheDocument();
    expect(screen.queryByText(/no vendors invited yet/i)).not.toBeInTheDocument();
    expect(screen.getByText(/live vendor roster unavailable/i)).toBeInTheDocument();
  });

  it('keeps the invite action disabled until approved vendors are selected and separates the two vendor lists', async () => {
    mockUseRfqVendors.mockReturnValue({
      data: [
        { id: 'inv-1', vendor_id: 'vendor-1', name: 'Alpha Vendor', status: 'responded', contact: 'alpha@example.com' },
      ],
      isLoading: false,
      isError: false,
      error: null,
    });

    renderWithProviders(<RfqVendorsPageContent rfqId="rfq-1" />);

    expect(await screen.findByRole('heading', { name: /approved vendor selection/i })).toBeInTheDocument();
    expect(await screen.findByRole('heading', { level: 1, name: /invited vendors/i })).toBeInTheDocument();
    expect(await screen.findByRole('button', { name: /invite vendors/i })).toBeDisabled();
    expect(await screen.findByText(/select approved vendors to enable invitations/i)).toBeInTheDocument();
    expect(await screen.findByText(/alpha vendor/i)).toBeInTheDocument();
  });

  it('invites selected vendors that are not already in the RFQ invitation roster', async () => {
    const inviteMutation = vi.fn().mockResolvedValue(undefined);
    mockUseInviteSelectedVendors.mockReturnValue({
      mutateAsync: inviteMutation,
      isPending: false,
      isError: false,
      error: null,
    });
    mockUseRequisitionVendorSelection.mockReturnValue({
      data: [
        {
          id: 'sel-1',
          rfqId: 'rfq-1',
          vendorId: 'vendor-1',
          vendorName: 'Alpha Vendor',
          vendorDisplayName: 'Alpha Vendor',
          vendorEmail: 'alpha@example.com',
          status: 'approved',
          selectedAt: '2026-04-22T10:00:00Z',
          selectedByUserId: 'user-1',
        },
        {
          id: 'sel-2',
          rfqId: 'rfq-1',
          vendorId: 'vendor-2',
          vendorName: 'Beta Vendor',
          vendorDisplayName: 'Beta Vendor',
          vendorEmail: 'beta@example.com',
          status: 'approved',
          selectedAt: '2026-04-22T10:00:00Z',
          selectedByUserId: 'user-1',
        },
      ],
      isLoading: false,
      isError: false,
      error: null,
    });
    mockUseRfqVendors.mockReturnValue({
      data: [
        { id: 'inv-1', vendor_id: 'vendor-1', name: 'Alpha Vendor', status: 'invited', contact: 'alpha@example.com' },
      ],
      isLoading: false,
      isError: false,
      error: null,
    });

    renderWithProviders(<RfqVendorsPageContent rfqId="rfq-1" />);

    fireEvent.click(await screen.findByRole('button', { name: /invite vendors/i }));

    await waitFor(() => expect(inviteMutation).toHaveBeenCalledWith({ vendorIds: ['vendor-2'] }));
  });

  it('shows scoped AI recommendation unavailability while keeping the manual selection workspace usable', async () => {
    mockUseAiStatus.mockReturnValue({
      isFeatureAvailable: () => false,
      shouldHideAiControls: () => true,
      shouldShowUnavailableMessage: (featureKey: string) => featureKey === 'vendor_ai_ranking',
      messageKeyForFeature: () => 'ai.status.unavailable',
      status: { mode: 'provider', globalHealth: 'degraded', providerName: 'OpenRouter' },
    });
    mockUseVendors.mockReturnValue({
      data: {
        items: [
          {
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
          },
        ],
        meta: { currentPage: 1, perPage: 25, total: 1, totalPages: 1 },
      },
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

    renderWithProviders(<RfqVendorsPageContent rfqId="rfq-1" />);

    expect(await screen.findByText(/ai recommendation is unavailable/i)).toBeInTheDocument();
    expect(screen.getByText(/you can still manually select vendors/i)).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: /approved vendor selection/i })).toBeInTheDocument();
    expect(screen.queryByText('Recommended')).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /why alpha procurement/i })).not.toBeInTheDocument();
  });
});
