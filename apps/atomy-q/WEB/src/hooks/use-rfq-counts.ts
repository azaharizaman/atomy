'use client';

import { useQuery } from '@tanstack/react-query';
import { fetchLiveOrFail } from '@/lib/api-live';

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

/**
 * Returns undefined for absent/blank fields; throws on malformed API numbers so React Query surfaces errors.
 */
function parseCount(raw: unknown): number | undefined {
  if (raw === null || raw === undefined) {
    return undefined;
  }
  if (typeof raw === 'string') {
    const t = raw.trim();
    if (t === '') {
      return undefined;
    }
    if (!/^-?\d+$/.test(t)) {
      throw new Error(`Invalid RFQ counts payload: expected integer string, got ${JSON.stringify(raw)}`);
    }
    return Number(t);
  }
  if (typeof raw === 'number') {
    if (!Number.isFinite(raw) || !Number.isInteger(raw)) {
      throw new Error('Invalid RFQ counts payload: expected finite integer number');
    }
    return raw;
  }
  if (typeof raw === 'boolean' || Array.isArray(raw)) {
    throw new Error('Invalid RFQ counts payload: unsupported type');
  }
  throw new Error(`Invalid RFQ counts payload: unsupported type ${typeof raw}`);
}

export function useRfqNavCounts() {
  return useQuery({
    queryKey: ['rfqs', 'counts'],
    queryFn: async (): Promise<RfqNavCounts> => {
      const data = await fetchLiveOrFail<{ data?: Partial<RfqNavCounts> }>('/rfqs/counts');

      if (data === undefined) {
        return {
          draft: 0,
          published: 0,
          closed: 0,
          awarded: 0,
          cancelled: 0,
          active: 0,
          pending: 0,
          archived: 0,
        };
      }

      const d = data?.data;
      const published = parseCount(d?.published) ?? 0;
      const cancelled = parseCount(d?.cancelled) ?? 0;
      return {
        draft: parseCount(d?.draft) ?? 0,
        published,
        closed: parseCount(d?.closed) ?? 0,
        awarded: parseCount(d?.awarded) ?? 0,
        cancelled,
        active: parseCount(d?.active) ?? published,
        pending: parseCount(d?.pending) ?? 0,
        archived: parseCount(d?.archived) ?? cancelled,
      };
    },
  });
}
