'use client';

import { useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';

export function useFreezeComparison() {
  const qc = useQueryClient();

  return useMutation({
    mutationFn: async (rfqId: string) => {
      const { data } = await api.post('/comparison-runs/final', { rfq_id: rfqId });
      return data;
    },
    onSuccess: (_data, rfqId) => {
      qc.invalidateQueries({ queryKey: ['rfqs', rfqId, 'overview'] });
      qc.invalidateQueries({ queryKey: ['comparison-runs', rfqId] });
      qc.invalidateQueries({ queryKey: ['decision-trail', rfqId] });
    },
  });
}
