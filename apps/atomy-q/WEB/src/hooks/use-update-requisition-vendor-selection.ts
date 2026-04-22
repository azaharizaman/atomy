'use client';

import { useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { useAuthStore } from '@/store/use-auth-store';
import { type RequisitionVendorSelectionRow, normalizeRequisitionVendorSelectionPayload } from '@/hooks/use-requisition-vendor-selection';

export interface UpdateRequisitionVendorSelectionInput {
  vendor_ids: string[];
}

async function invokeSelectionUpdate(rfqId: string, input: UpdateRequisitionVendorSelectionInput): Promise<RequisitionVendorSelectionRow[]> {
  const token = useAuthStore.getState().token;
  const response = await api.put(`/rfqs/${encodeURIComponent(rfqId)}/selected-vendors`, input, {
    headers: token ? { Authorization: `Bearer ${token}` } : undefined,
  });

  return normalizeRequisitionVendorSelectionPayload(response.data);
}

export function useUpdateRequisitionVendorSelection(rfqId: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (input: UpdateRequisitionVendorSelectionInput): Promise<RequisitionVendorSelectionRow[]> =>
      invokeSelectionUpdate(rfqId, input),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['rfqs', rfqId] });
    },
  });
}
