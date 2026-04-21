import React from 'react';
import { describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

import { renderWithProviders } from '@/test/utils';

const mockUseVendor = vi.fn();
const mockUseUpdateVendor = vi.fn();
const mockUseUpdateVendorStatus = vi.fn();

vi.mock('@/hooks/use-vendor', () => ({
  useVendor: (...args: unknown[]) => mockUseVendor(...args),
}));

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
});
