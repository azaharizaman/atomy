'use client';

import { useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';

export interface InviteSelectedVendorsInput {
  vendorIds: string[];
}

export function useInviteSelectedVendors(rfqId: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ vendorIds }: InviteSelectedVendorsInput): Promise<void> => {
      for (const vendorId of vendorIds) {
        await api.post(`/rfqs/${encodeURIComponent(rfqId)}/invitations`, {
          vendor_id: vendorId,
          channel: 'email',
        });
      }
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['rfqs', rfqId] });
    },
  });
}
