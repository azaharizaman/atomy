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

type VendorMutationBody = {
  legal_name: string;
  display_name: string;
  registration_number: string;
  country_of_registration: string;
  primary_contact_name: string;
  primary_contact_email: string;
  primary_contact_phone: string | null;
};

function toVendorMutationBody(input: VendorUpsertInput): VendorMutationBody {
  return {
    legal_name: input.legalName,
    display_name: input.displayName,
    registration_number: input.registrationNumber,
    country_of_registration: input.countryOfRegistration,
    primary_contact_name: input.primaryContactName,
    primary_contact_email: input.primaryContactEmail,
    primary_contact_phone: input.primaryContactPhone ?? null,
  };
}

async function invokeVendorUpdate(vendorId: string, input: VendorUpsertInput): Promise<VendorRow> {
  const token = useAuthStore.getState().token;

  try {
    // Generated OpenAPI currently omits the PUT /vendors/{id} request body type.
    const response = await vendorUpdate({
      path: { id: vendorId },
      body: toVendorMutationBody(input),
      credentials: 'include',
      headers: token ? { Authorization: `Bearer ${token}` } : undefined,
      throwOnError: true,
    } as unknown as Parameters<typeof vendorUpdate>[0]);

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
