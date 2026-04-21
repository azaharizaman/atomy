import React from 'react';
import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen } from '@testing-library/react';

import { renderWithProviders } from '@/test/utils';

const mockUseVendors = vi.fn();
const mockUseCreateVendor = vi.fn();

vi.mock('@/hooks/use-vendors', () => ({
  useVendors: (...args: unknown[]) => mockUseVendors(...args),
}));

vi.mock('@/hooks/use-create-vendor', () => ({
  useCreateVendor: (...args: unknown[]) => mockUseCreateVendor(...args),
}));

import { VendorsPageContent } from './page';

describe('VendorsPage', () => {
  it('renders loading state while vendors are being fetched', () => {
    mockUseVendors.mockReturnValue({ data: undefined, isLoading: true, isError: false, error: null });
    mockUseCreateVendor.mockReturnValue({ mutate: vi.fn(), isPending: false, isError: false, error: null });

    renderWithProviders(<VendorsPageContent />);

    expect(screen.getAllByText(/loading vendors/i).length).toBeGreaterThan(0);
    expect(screen.queryByText(/no vendors found/i)).not.toBeInTheDocument();
  });

  it('renders an error state when the vendor query fails', () => {
    mockUseVendors.mockReturnValue({
      data: undefined,
      isLoading: false,
      isError: true,
      error: new Error('Live vendors unavailable'),
    });
    mockUseCreateVendor.mockReturnValue({ mutate: vi.fn(), isPending: false, isError: false, error: null });

    renderWithProviders(<VendorsPageContent />);

    expect(screen.getByText(/could not load vendors/i)).toBeInTheDocument();
    expect(screen.getByText(/live vendors unavailable/i)).toBeInTheDocument();
  });

  it('renders the empty state when no vendors exist', () => {
    mockUseVendors.mockReturnValue({
      data: { items: [], meta: { currentPage: 1, perPage: 25, total: 0, totalPages: 1 } },
      isLoading: false,
      isError: false,
      error: null,
    });
    mockUseCreateVendor.mockReturnValue({ mutate: vi.fn(), isPending: false, isError: false, error: null });

    renderWithProviders(<VendorsPageContent />);

    expect(screen.getByText(/no vendors found/i)).toBeInTheDocument();
    expect(screen.getAllByRole('button', { name: /create vendor/i }).length).toBeGreaterThan(0);
  });

  it('renders populated rows, status filters, and the create action', () => {
    const createVendor = vi.fn();
    mockUseVendors.mockReturnValue({
      data: {
        items: [
          {
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
          },
        ],
        meta: { currentPage: 1, perPage: 25, total: 1, totalPages: 1 },
      },
      isLoading: false,
      isError: false,
      error: null,
    });
    mockUseCreateVendor.mockReturnValue({ mutate: createVendor, isPending: false, isError: false, error: null });

    renderWithProviders(<VendorsPageContent />);

    expect(screen.getByText('Northwind')).toBeInTheDocument();
    expect(screen.getByRole('status', { name: 'Approved' })).toBeInTheDocument();
    expect(screen.getByText(/status filter/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/search vendors/i)).toBeInTheDocument();

    fireEvent.click(screen.getByRole('button', { name: /create vendor/i }));
    fireEvent.change(screen.getByLabelText(/legal name/i), { target: { value: 'Acme Manufacturing' } });
    fireEvent.change(screen.getByLabelText(/display name/i), { target: { value: 'Acme' } });
    fireEvent.change(screen.getByLabelText(/registration number/i), { target: { value: 'REG-200' } });
    fireEvent.change(screen.getByLabelText(/country of registration/i), { target: { value: 'MY' } });
    fireEvent.change(screen.getByLabelText(/primary contact name/i), { target: { value: 'Rina Tan' } });
    fireEvent.change(screen.getByLabelText(/primary contact email/i), { target: { value: 'rina@example.com' } });
    fireEvent.click(screen.getByRole('button', { name: /submit vendor/i }));

    expect(createVendor).toHaveBeenCalledWith(
      {
        legalName: 'Acme Manufacturing',
        displayName: 'Acme',
        registrationNumber: 'REG-200',
        countryOfRegistration: 'MY',
        primaryContactName: 'Rina Tan',
        primaryContactEmail: 'rina@example.com',
        primaryContactPhone: null,
      },
      expect.any(Object),
    );
  });

  it('rejects unsupported country codes before creating a vendor', () => {
    const createVendor = vi.fn();
    mockUseVendors.mockReturnValue({
      data: { items: [], meta: { currentPage: 1, perPage: 25, total: 0, totalPages: 1 } },
      isLoading: false,
      isError: false,
      error: null,
    });
    mockUseCreateVendor.mockReturnValue({ mutate: createVendor, isPending: false, isError: false, error: null });

    renderWithProviders(<VendorsPageContent />);

    fireEvent.click(screen.getAllByRole('button', { name: /create vendor/i })[0]);
    fireEvent.change(screen.getByLabelText(/legal name/i), { target: { value: 'Acme Manufacturing' } });
    fireEvent.change(screen.getByLabelText(/display name/i), { target: { value: 'Acme' } });
    fireEvent.change(screen.getByLabelText(/registration number/i), { target: { value: 'REG-200' } });
    fireEvent.change(screen.getByLabelText(/country of registration/i), { target: { value: 'Malaysia' } });
    fireEvent.change(screen.getByLabelText(/primary contact name/i), { target: { value: 'Rina Tan' } });
    fireEvent.change(screen.getByLabelText(/primary contact email/i), { target: { value: 'rina@example.com' } });
    fireEvent.click(screen.getByRole('button', { name: /submit vendor/i }));

    expect(createVendor).not.toHaveBeenCalled();
    expect(screen.getByText(/country of registration must be a valid two-letter iso 3166-1 alpha-2 code/i)).toBeInTheDocument();
  });
});
