'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { type RfqStatus, isValidRfqStatus, RFQ_STATUSES } from '@/hooks/use-rfqs';

export interface RfqDetail {
  id: string;
  title: string;
  status: RfqStatus;
  vendorsCount?: number;
  quotesCount?: number;
  estValue?: string;
  savings?: string;
}

function normalizeRfq(payload: unknown): RfqDetail {
  let raw = payload as Record<string, unknown> | null | undefined;
  while (raw?.data) {
    raw = raw.data as Record<string, unknown>;
  }
  const rawStatus = raw?.status;
  const status: RfqStatus = isValidRfqStatus(rawStatus) ? rawStatus : RFQ_STATUSES.ACTIVE;
  return {
    id: String(raw?.id ?? raw?.rfqId ?? ''),
    title: String(raw?.title ?? raw?.name ?? 'Requisition'),
    status,
    vendorsCount: (raw?.vendorsCount ?? raw?.vendors_count) as number | undefined,
    quotesCount: (raw?.quotesCount ?? raw?.quotes_count) as number | undefined,
    estValue: (raw?.estValue ?? raw?.estimated_value ?? raw?.estimatedValue) as string | undefined,
    savings: raw?.savings as string | undefined,
  };
}

export function useRfq(rfqId: string) {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

  return useQuery({
    queryKey: ['rfqs', rfqId],
    queryFn: async () => {
      if (useMocks) {
        const { getSeedRfqDetail } = await import('@/data/seed');
        const detail = getSeedRfqDetail(rfqId);
        if (!detail) throw new Error('RFQ not found');
        return normalizeRfq(detail);
      }

      const { data } = await api.get(`/rfqs/${encodeURIComponent(rfqId)}`);
      return normalizeRfq(data);
    },
  });
}

