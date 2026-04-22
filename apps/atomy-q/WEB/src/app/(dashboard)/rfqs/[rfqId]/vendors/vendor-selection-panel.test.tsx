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
      data: { candidates: [], excludedReasons: [] },
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

    renderWithProviders(<RfqVendorSelectionPanel rfqId="rfq-1" />);

    expect(screen.getByText('Alpha Procurement')).toBeInTheDocument();
    expect(screen.getByText('Beta Supply')).toBeInTheDocument();
    expect(screen.queryByText('Gamma Restricted')).not.toBeInTheDocument();
    expect(screen.getByRole('checkbox', { name: /alpha procurement/i })).toBeChecked();
    expect(screen.getByRole('checkbox', { name: /beta supply/i })).not.toBeChecked();

    fireEvent.change(screen.getByLabelText(/search approved vendors/i), { target: { value: 'beta' } });

    await waitFor(() => expect(mockUseVendors).toHaveBeenCalledWith(expect.objectContaining({ q: 'beta' })));
    expect(screen.queryByText('Alpha Procurement')).not.toBeInTheDocument();
    expect(screen.getByText('Beta Supply')).toBeInTheDocument();

    fireEvent.click(screen.getByRole('checkbox', { name: /beta supply/i }));
    fireEvent.click(screen.getByRole('button', { name: /save selection/i }));

    await waitFor(() =>
      expect(saveMutation).toHaveBeenCalledWith({
        vendor_ids: ['vendor-1', 'vendor-2'],
      }),
    );
  });

  it('shows an empty state that points to the vendor master when no approved vendors exist', () => {
    mockUseVendors.mockReturnValue({
      data: { items: [], meta: { currentPage: 1, perPage: 25, total: 0, totalPages: 1 } },
      isLoading: false,
      isError: false,
      error: null,
    });

    renderWithProviders(<RfqVendorSelectionPanel rfqId="rfq-1" />);

    expect(screen.getByText(/no approved vendors available/i)).toBeInTheDocument();
    expect(screen.getByRole('link', { name: /browse vendors/i })).toHaveAttribute('href', '/vendors');
  });
});
