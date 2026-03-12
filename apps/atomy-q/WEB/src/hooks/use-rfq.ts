'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';

export interface RfqDetail {
  id: string;
  title: string;
  status: string;
  vendorsCount?: number;
  quotesCount?: number;
  estValue?: string;
  savings?: string;
}

function normalizeRfq(payload: any): RfqDetail {
  const raw = payload?.data ?? payload;
  return {
    id: String(raw?.id ?? raw?.rfqId ?? ''),
    title: String(raw?.title ?? raw?.name ?? 'Requisition'),
    status: String(raw?.status ?? 'active'),
    vendorsCount: raw?.vendorsCount ?? raw?.vendors_count,
    quotesCount: raw?.quotesCount ?? raw?.quotes_count,
    estValue: raw?.estValue ?? raw?.estimated_value ?? raw?.estimatedValue,
    savings: raw?.savings,
  };
}

export function useRfq(rfqId: string) {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

  return useQuery({
    queryKey: ['rfqs', rfqId],
    queryFn: async () => {
      if (useMocks) {
        return normalizeRfq({
          id: rfqId,
          title: rfqId === 'RFQ-2401' ? 'Server Infrastructure Refresh' : 'Requisition Workspace',
          status: 'active',
          vendorsCount: 5,
          quotesCount: 8,
          estValue: '$1.2M',
          savings: '12%',
        });
      }

      const { data } = await api.get(`/rfqs/${encodeURIComponent(rfqId)}`);
      return normalizeRfq(data);
    },
  });
}

