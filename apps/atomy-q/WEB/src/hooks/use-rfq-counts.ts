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

function parseCount(raw: unknown, fallback: number): number {
  if (raw === null || raw === undefined) return fallback;
  if (typeof raw === 'string' && raw.trim() === '') return fallback;
  const n = Number(typeof raw === 'string' ? raw.trim() : raw);
  if (!Number.isFinite(n)) return fallback;
  return Math.trunc(n);
}

export function useRfqNavCounts() {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

  return useQuery({
    queryKey: ['rfqs', 'counts'],
    queryFn: async (): Promise<RfqNavCounts> => {
      const { data } = await api.get<{ data?: Partial<RfqNavCounts> }>('/rfqs/counts');
      const d = data?.data;
      const published = parseCount(d?.published, 0);
      const cancelled = parseCount(d?.cancelled, 0);
      return {
        draft: parseCount(d?.draft, 0),
        published,
        closed: parseCount(d?.closed, 0),
        awarded: parseCount(d?.awarded, 0),
        cancelled,
        active: parseCount(d?.active, published),
        pending: parseCount(d?.pending, 0),
        archived: parseCount(d?.archived, cancelled),
      };
    },
    enabled: !useMocks,
    initialData: useMocks ? emptyCounts : undefined,
  });
}
