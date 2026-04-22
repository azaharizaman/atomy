import React from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

import { renderWithProviders } from '@/test/utils';

const mockUseVendor = vi.fn();
const mockUseVendorGovernance = vi.fn();
const mockUseUpdateVendor = vi.fn();
const mockUseUpdateVendorStatus = vi.fn();

vi.mock('@/hooks/use-vendor', () => ({
  useVendor: (...args: unknown[]) => mockUseVendor(...args),
}));

vi.mock('@/hooks/use-vendor-governance', async () => {
  const actual = await vi.importActual<typeof import('@/hooks/use-vendor-governance')>('@/hooks/use-vendor-governance');
  return {
    ...actual,
    useVendorGovernance: (...args: unknown[]) => mockUseVendorGovernance(...args),
  };
});

vi.mock('@/hooks/use-update-vendor-status', () => ({
  useUpdateVendorStatus: (...args: unknown[]) => mockUseUpdateVendorStatus(...args),
}));

vi.mock('@/hooks/use-update-vendor', () => ({
  useUpdateVendor: (...args: unknown[]) => mockUseUpdateVendor(...args),
}));

import { VendorDetailPageContent } from './page';

describe('VendorDetailPage', () => {
  const baseVendor = {
    id: 'ven-1',
    legalName: 'Northwind Holdings Limited',
    displayName: 'Northwind',
    registrationNumber: 'REG-100',
    countryOfRegistration: 'SG',
    primaryContactName: 'Alicia Wong',
    primaryContactEmail: 'alicia@example.com',
    primaryContactPhone: '+65 9000 1111',
    status: 'approved',
    approvalRecord: {
      approvedByUserId: 'user-1',
      approvedAt: '2026-04-22T10:00:00Z',
      approvalNote: 'Approved after due diligence',
    },
    createdAt: '2026-04-20T10:00:00Z',
    updatedAt: '2026-04-21T10:00:00Z',
    name: 'Northwind Holdings Limited',
    tradingName: 'Northwind',
    countryCode: 'SG',
    email: 'alicia@example.com',
    phone: '+65 9000 1111',
  } as const;

  beforeEach(() => {
    vi.clearAllMocks();
    mockUseVendorGovernance.mockReturnValue({
      data: {
        vendorId: 'ven-1',
        evidence: [],
        findings: [],
        scores: {
          esgScore: 100,
          complianceHealthScore: 100,
          riskWatchScore: 100,
          evidenceFreshnessScore: 100,
        },
        warningFlags: [],
      },
      isLoading: false,
      isError: false,
      error: null,
    });
  });

  it('renders approval metadata and status controls for an approved vendor', () => {
    const mutate = vi.fn();

    mockUseVendor.mockReturnValue({
      data: baseVendor,
      isLoading: false,
      isError: false,
      error: null,
    });
    mockUseUpdateVendor.mockReturnValue({ mutate: vi.fn(), isPending: false, isError: false, error: null });
    mockUseUpdateVendorStatus.mockReturnValue({ mutate, isPending: false, isError: false, error: null });

    renderWithProviders(<VendorDetailPageContent vendorId="ven-1" />);

    expect(screen.getByText('Northwind')).toBeInTheDocument();
    expect(screen.getByText(/approval metadata/i)).toBeInTheDocument();
    expect(screen.getByText(/approved by user-1/i)).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /approve/i })).not.toBeInTheDocument();
    expect(screen.getByRole('button', { name: /restrict/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /suspend/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /archive/i })).toBeInTheDocument();
  });

  it('renders governance warning chips on the overview', () => {
    mockUseVendor.mockReturnValue({
      data: baseVendor,
      isLoading: false,
      isError: false,
      error: null,
    });
    mockUseVendorGovernance.mockReturnValue({
      data: {
        vendorId: 'ven-1',
        evidence: [],
        findings: [],
        scores: {
          esgScore: 65,
          complianceHealthScore: 50,
          riskWatchScore: 45,
          evidenceFreshnessScore: 40,
        },
        warningFlags: ['compliance_document_expired', 'open_severe_risk_finding'],
      },
      isLoading: false,
      isError: false,
      error: null,
    });
    mockUseUpdateVendor.mockReturnValue({ mutate: vi.fn(), isPending: false, isError: false, error: null });
    mockUseUpdateVendorStatus.mockReturnValue({ mutate: vi.fn(), isPending: false, isError: false, error: null });

    renderWithProviders(<VendorDetailPageContent vendorId="ven-1" />);

    expect(screen.getByText('Compliance Document Expired')).toBeInTheDocument();
    expect(screen.getByText('Open Severe Risk Finding')).toBeInTheDocument();
    expect(screen.getByRole('link', { name: /review/i })).toHaveAttribute('href', '/vendors/ven-1/esg-compliance');
  });

  it('renders under-review transition for draft vendor', () => {
    mockUseVendor.mockReturnValue({
      data: {
        id: 'ven-1',
        legalName: 'Northwind Holdings Limited',
        displayName: 'Northwind',
        registrationNumber: 'REG-100',
        countryOfRegistration: 'SG',
        primaryContactName: 'Alicia Wong',
        primaryContactEmail: 'alicia@example.com',
        primaryContactPhone: null,
        status: 'draft',
        approvalRecord: null,
        createdAt: '2026-04-20T10:00:00Z',
        updatedAt: '2026-04-21T10:00:00Z',
        name: 'Northwind Holdings Limited',
        tradingName: 'Northwind',
        countryCode: 'SG',
        email: 'alicia@example.com',
        phone: null,
      },
      isLoading: false,
      isError: false,
      error: null,
    });
    mockUseUpdateVendor.mockReturnValue({ mutate: vi.fn(), isPending: false, isError: false, error: null });
    mockUseUpdateVendorStatus.mockReturnValue({ mutate: vi.fn(), isPending: false, isError: false, error: null });

    renderWithProviders(<VendorDetailPageContent vendorId="ven-1" />);

    expect(screen.getByRole('button', { name: /move to under review/i })).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /restrict/i })).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /suspend/i })).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /archive/i })).not.toBeInTheDocument();
  });

  it('renders status-action errors when an update fails', () => {
    mockUseVendor.mockReturnValue({
      data: {
        id: 'ven-1',
        legalName: 'Northwind Holdings Limited',
        displayName: 'Northwind',
        registrationNumber: 'REG-100',
        countryOfRegistration: 'SG',
        primaryContactName: 'Alicia Wong',
        primaryContactEmail: 'alicia@example.com',
        primaryContactPhone: '+65 9000 1111',
        status: 'under_review',
        approvalRecord: null,
        createdAt: '2026-04-20T10:00:00Z',
        updatedAt: '2026-04-21T10:00:00Z',
        name: 'Northwind Holdings Limited',
        tradingName: 'Northwind',
        countryCode: 'SG',
        email: 'alicia@example.com',
        phone: '+65 9000 1111',
      },
      isLoading: false,
      isError: false,
      error: null,
    });
    mockUseUpdateVendor.mockReturnValue({ mutate: vi.fn(), isPending: false, isError: false, error: null });
    mockUseUpdateVendorStatus.mockReturnValue({
      mutate: vi.fn(),
      isPending: false,
      isError: true,
      error: new Error('Status update failed'),
    });

    renderWithProviders(<VendorDetailPageContent vendorId="ven-1" />);

    expect(screen.getByText(/status update failed/i)).toBeInTheDocument();
  });

  it('submits edited core vendor fields from detail page', async () => {
    const mutate = vi.fn();
    const user = userEvent.setup();

    mockUseVendor.mockReturnValue({
      data: baseVendor,
      isLoading: false,
      isError: false,
      error: null,
    });
    mockUseUpdateVendor.mockReturnValue({ mutate, isPending: false, isError: false, error: null });
    mockUseUpdateVendorStatus.mockReturnValue({ mutate: vi.fn(), isPending: false, isError: false, error: null });

    renderWithProviders(<VendorDetailPageContent vendorId="ven-1" />);

    await user.click(screen.getByRole('button', { name: /edit vendor/i }));
    await user.clear(screen.getByLabelText(/display name/i));
    await user.type(screen.getByLabelText(/display name/i), 'Northwind Updated');
    await user.clear(screen.getByLabelText(/primary contact phone/i));
    await user.click(screen.getByRole('button', { name: /save vendor/i }));

    expect(mutate).toHaveBeenCalledWith(
      {
        legalName: 'Northwind Holdings Limited',
        displayName: 'Northwind Updated',
        registrationNumber: 'REG-100',
        countryOfRegistration: 'SG',
        primaryContactName: 'Alicia Wong',
        primaryContactEmail: 'alicia@example.com',
        primaryContactPhone: null,
      },
      expect.any(Object),
    );
  });

  it('requires an approval note before approving a vendor', async () => {
    const mutate = vi.fn();
    const user = userEvent.setup();

    mockUseVendor.mockReturnValue({
      data: {
        ...baseVendor,
        status: 'under_review',
        approvalRecord: null,
      },
      isLoading: false,
      isError: false,
      error: null,
    });
    mockUseUpdateVendor.mockReturnValue({ mutate: vi.fn(), isPending: false, isError: false, error: null });
    mockUseUpdateVendorStatus.mockReturnValue({ mutate, isPending: false, isError: false, error: null });

    renderWithProviders(<VendorDetailPageContent vendorId="ven-1" />);

    await user.click(screen.getByRole('button', { name: /^approve$/i }));

    expect(mutate).not.toHaveBeenCalled();
    expect(screen.getByText(/approval note is required/i)).toBeInTheDocument();
  });
});
