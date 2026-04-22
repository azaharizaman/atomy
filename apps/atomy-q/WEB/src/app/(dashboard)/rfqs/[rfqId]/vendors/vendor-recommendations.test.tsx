import { describe, expect, it, vi, beforeEach } from 'vitest';
import { fireEvent, screen, waitFor } from '@testing-library/react';

import { renderWithProviders } from '@/test/utils';

const mockUseVendors = vi.fn();
const mockUseRequisitionVendorSelection = vi.fn();
const mockUseUpdateRequisitionVendorSelection = vi.fn();
const mockUseVendorRecommendations = vi.fn();

vi.mock('@/hooks/use-vendors', () => ({
  useVendors: (...args: unknown[]) => mockUseVendors(...args),
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

import { RfqVendorSelectionPanel } from './page';

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
    mockUseVendorRecommendations.mockReturnValue({
      data: {
        candidates: [
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
        ],
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
  });

  it('prefills recommendations, allows user override, and shows explanation details', async () => {
    const saveMutation = vi.fn().mockResolvedValue(undefined);
    mockUseUpdateRequisitionVendorSelection.mockReturnValue({
      mutateAsync: saveMutation,
      isPending: false,
      isError: false,
      error: null,
    });

    renderWithProviders(<RfqVendorSelectionPanel rfqId="rfq-1" />);

    expect(screen.getByRole('checkbox', { name: /alpha procurement/i })).toBeChecked();
    expect(screen.getByText('Recommended')).toBeInTheDocument();

    fireEvent.click(screen.getByRole('button', { name: /why alpha procurement/i }));
    expect(screen.getByText('Strong facilities category fit.')).toBeInTheDocument();
    expect(screen.getByText('Category overlap: facilities.')).toBeInTheDocument();
    expect(screen.getByText('No recent activity signal available.')).toBeInTheDocument();

    fireEvent.click(screen.getByRole('checkbox', { name: /alpha procurement/i }));
    fireEvent.click(screen.getByRole('checkbox', { name: /beta supply/i }));
    fireEvent.click(screen.getByRole('button', { name: /save selection/i }));

    await waitFor(() =>
      expect(saveMutation).toHaveBeenCalledWith({
        vendor_ids: ['vendor-2'],
      }),
    );
  });
});
