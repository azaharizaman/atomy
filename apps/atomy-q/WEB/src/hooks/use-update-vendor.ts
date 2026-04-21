'use client';

import { useMutation, useQueryClient } from '@tanstack/react-query';
import { vendorUpdate } from '@/generated/api/sdk.gen';
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

  try {
    const response = await vendorUpdate({
      path: { id: vendorId },
      body: {
        legal_name: input.legalName,
        display_name: input.displayName,
        registration_number: input.registrationNumber,
        country_of_registration: input.countryOfRegistration,
        primary_contact_name: input.primaryContactName,
        primary_contact_email: input.primaryContactEmail,
        primary_contact_phone: input.primaryContactPhone ?? null,
      },
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
