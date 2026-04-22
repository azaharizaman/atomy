'use client';

import { useMutation, useQueryClient } from '@tanstack/react-query';
import { vendorStatusUpdate } from '@/generated/api/sdk.gen';
import {
  extractResponseErrorMessage,
  normalizeVendorResponse,
  type VendorRow,
  type VendorStatusUpdateInput,
} from '@/hooks/use-vendors';
import { useAuthStore } from '@/store/use-auth-store';

export type { VendorRow, VendorStatusUpdateInput } from '@/hooks/use-vendors';

async function invokeVendorStatusUpdate(vendorId: string, input: VendorStatusUpdateInput): Promise<VendorRow> {
  const token = useAuthStore.getState().token;

  try {
    const response = await vendorStatusUpdate({
      path: { id: vendorId },
      body: {
        status: input.status,
        approval_note: input.approvalNote ?? undefined,
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

export function useUpdateVendorStatus(vendorId: string) {
  const queryClient = useQueryClient();
  const normalizedVendorId = vendorId.trim();

  return useMutation({
    mutationFn: async (input: VendorStatusUpdateInput): Promise<VendorRow> =>
      invokeVendorStatusUpdate(normalizedVendorId, input),
    onSuccess: async (updatedVendor) => {
      await queryClient.invalidateQueries({ queryKey: ['vendors'] });
      await queryClient.invalidateQueries({ queryKey: ['vendors', updatedVendor.id] });
    },
  });
}
