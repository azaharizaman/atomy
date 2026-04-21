'use client';

import { useQuery } from '@tanstack/react-query';
import { vendorShow } from '@/generated/api/sdk.gen';
import { extractResponseErrorMessage, normalizeVendorResponse } from '@/hooks/use-vendors';
import { useAuthStore } from '@/store/use-auth-store';

export { normalizeVendorResponse } from '@/hooks/use-vendors';
export type { VendorApprovalRecord, VendorListFilters, VendorListMeta, VendorListResult, VendorRow, VendorStatusValue, VendorUpsertInput } from '@/hooks/use-vendors';

async function invokeVendorShow(vendorId: string) {
  const token = useAuthStore.getState().token;

  try {
    const response = await vendorShow({
      path: { id: vendorId },
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

export function useVendor(vendorId: string) {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';
  const normalizedVendorId = vendorId.trim();

  return useQuery({
    queryKey: ['vendors', normalizedVendorId],
    enabled: !useMocks && normalizedVendorId !== '',
    queryFn: async () => invokeVendorShow(normalizedVendorId),
  });
}
