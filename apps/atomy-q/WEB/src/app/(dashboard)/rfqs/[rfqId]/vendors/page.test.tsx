import { beforeAll, beforeEach, describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import { renderWithProviders } from '@/test/utils';

const mockUseRfqVendors = vi.fn();
const mockUseVendors = vi.fn();
const mockUseRequisitionVendorSelection = vi.fn();
const mockUseUpdateRequisitionVendorSelection = vi.fn();

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

import { RfqVendorsPageContent } from './page';

describe('RfqVendorsPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
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
});
