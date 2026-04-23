'use client';

import { useMutation, useQueryClient } from '@tanstack/react-query';
import { vendorUpdate } from '@/generated/api/sdk.gen';
import type { VendorUpdateData } from '@/generated/api/types.gen';
import {
  extractResponseErrorMessage,
  normalizeVendorResponse,
  type VendorRow,
  type VendorUpsertInput,
} from '@/hooks/use-vendors';
import { useAuthStore } from '@/store/use-auth-store';

export type { VendorRow, VendorUpsertInput } from '@/hooks/use-vendors';

async function invokeVendorUpdate(vendorId: string, input: VendorUpsertInput): Promise<VendorRow> {
  const token = useAuthStore.getState().token;
  const body: Partial<VendorUpdateData['body']> = {};

  if (input.legalName.trim() !== '') {
    body.legal_name = input.legalName.trim();
  }
  if (input.displayName.trim() !== '') {
    body.display_name = input.displayName.trim();
  }
  if (input.registrationNumber.trim() !== '') {
    body.registration_number = input.registrationNumber.trim();
  }
  if (input.countryOfRegistration.trim() !== '') {
    body.country_of_registration = input.countryOfRegistration.trim();
  }
  if (input.primaryContactName.trim() !== '') {
    body.primary_contact_name = input.primaryContactName.trim();
  }
  if (input.primaryContactEmail.trim() !== '') {
    body.primary_contact_email = input.primaryContactEmail.trim();
  }
  if (input.primaryContactPhone !== undefined) {
    const trimmedPhone = input.primaryContactPhone === null ? null : input.primaryContactPhone.trim();
    body.primary_contact_phone = trimmedPhone === null || trimmedPhone !== '' ? trimmedPhone : null;
  }

  try {
    const response = await vendorUpdate({
      path: { id: vendorId },
      body: body as VendorUpdateData['body'],
      credentials: 'include',
      headers: token ? { Authorization: `Bearer ${token}` } : undefined,
      throwOnError: true,
    });

    if (response === undefined) {
      throw new Error('Invalid vendor payload: missing response.');
    }

    return normalizeVendorResponse(response.data);
  } catch (error: unknown) {
    throw new Error(extractResponseErrorMessage(error));
  }
}

export function useUpdateVendor(vendorId: string) {
  const queryClient = useQueryClient();
  const normalizedVendorId = vendorId.trim();

  return useMutation({
    mutationFn: async (input: VendorUpsertInput): Promise<VendorRow> => invokeVendorUpdate(normalizedVendorId, input),
    onSuccess: async (updatedVendor) => {
      await queryClient.invalidateQueries({ queryKey: ['vendors'] });
      await queryClient.invalidateQueries({ queryKey: ['vendors', updatedVendor.id] });
    },
  });
}
