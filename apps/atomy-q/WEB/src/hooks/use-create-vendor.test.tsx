import React from 'react';
import { act, renderHook, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { createTestWrapper } from '@/test/utils';
import { useAuthStore } from '@/store/use-auth-store';

const mockVendorStore = vi.fn();

vi.mock('@/generated/api/sdk.gen', () => ({
  v1VendorsStore: (...args: unknown[]) => mockVendorStore(...args),
  vendorIndex: vi.fn(),
}));

import { extractResponseErrorMessage } from '@/hooks/use-vendors';
import { useCreateVendor } from '@/hooks/use-create-vendor';

const vendorPayload = {
  data: {
    id: 'vendor-1',
    legal_name: 'Acme Manufacturing Sdn Bhd',
    display_name: 'Acme',
    registration_number: 'REG-200',
    country_of_registration: 'MY',
    primary_contact_name: 'Rina Tan',
    primary_contact_email: 'rina@example.com',
    primary_contact_phone: null,
    status: 'draft',
    approval_record: null,
    created_at: '2026-05-04T01:00:00Z',
    updated_at: '2026-05-04T01:00:00Z',
    name: 'Acme Manufacturing Sdn Bhd',
    trading_name: 'Acme',
    country_code: 'MY',
    email: 'rina@example.com',
    phone: null,
  },
};

const createInput = {
  legalName: 'Acme Manufacturing Sdn Bhd',
  displayName: 'Acme',
  registrationNumber: 'REG-200',
  countryOfRegistration: 'MY',
  primaryContactName: 'Rina Tan',
  primaryContactEmail: 'rina@example.com',
  primaryContactPhone: null,
};

describe('useCreateVendor', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    useAuthStore.setState({ token: 'access-token' });
    vi.spyOn(crypto, 'randomUUID').mockReturnValue('vendor-create-key');
  });

  it('sends an idempotency key when creating vendors', async () => {
    mockVendorStore.mockResolvedValue({ data: vendorPayload });
    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useCreateVendor(), { wrapper: Wrapper });

    await act(async () => {
      await result.current.mutateAsync(createInput);
    });

    await waitFor(() => expect(mockVendorStore).toHaveBeenCalled());
    expect(mockVendorStore).toHaveBeenCalledWith(
      expect.objectContaining({
        headers: expect.objectContaining({
          Authorization: 'Bearer access-token',
          'Idempotency-Key': 'vendor-create-key',
        }),
      }),
    );
  });

  it('normalizes the API create response without requiring display aliases', async () => {
    mockVendorStore.mockResolvedValue({
      data: {
        data: {
          id: 'vendor-1',
          display_identifier: 'Acme',
          legal_name: 'Acme Manufacturing Sdn Bhd',
          display_name: 'Acme',
          registration_number: 'REG-200',
          country_of_registration: 'MY',
          primary_contact_name: 'Rina Tan',
          primary_contact_email: 'rina@example.com',
          primary_contact_phone: null,
          status: 'draft',
          approval_record: null,
          created_at: '2026-05-04T01:00:00Z',
          updated_at: '2026-05-04T01:00:00Z',
        },
      },
    });
    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useCreateVendor(), { wrapper: Wrapper });

    await act(async () => {
      await result.current.mutateAsync(createInput);
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toMatchObject({
      legalName: 'Acme Manufacturing Sdn Bhd',
      displayName: 'Acme',
      name: 'Acme',
      tradingName: 'Acme',
      countryCode: 'MY',
      email: 'rina@example.com',
      phone: null,
    });
  });

  it('maps technical idempotency errors to a vendor-friendly save message', () => {
    expect(
      extractResponseErrorMessage({
        response: {
          data: {
            message: 'Idempotency key header is required.',
          },
        },
      }),
    ).toBe('We could not save the vendor. Please try again.');
  });
});
