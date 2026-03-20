'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';

export interface RfqNavCounts {
  draft: number;
  published: number;
  closed: number;
  awarded: number;
  cancelled: number;
  active: number;
  pending: number;
  archived: number;
}

const emptyCounts: RfqNavCounts = {
  draft: 0,
  published: 0,
  closed: 0,
  awarded: 0,
  cancelled: 0,
  active: 0,
  pending: 0,
  archived: 0,
};

export function useRfqNavCounts() {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

  return useQuery({
    queryKey: ['rfqs', 'counts'],
    queryFn: async (): Promise<RfqNavCounts> => {
      const { data } = await api.get<{ data?: Partial<RfqNavCounts> }>('/rfqs/counts');
      const d = data?.data;
      return {
        draft: Number(d?.draft ?? 0),
        published: Number(d?.published ?? 0),
        closed: Number(d?.closed ?? 0),
        awarded: Number(d?.awarded ?? 0),
        cancelled: Number(d?.cancelled ?? 0),
        active: Number(d?.active ?? d?.published ?? 0),
        pending: Number(d?.pending ?? 0),
        archived: Number(d?.archived ?? d?.cancelled ?? 0),
      };
    },
    enabled: !useMocks,
    initialData: useMocks ? emptyCounts : undefined,
  });
}
