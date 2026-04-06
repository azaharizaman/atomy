'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { getSeedComparisonRunsByRfqId } from '@/data/seed';

export interface ComparisonRunRow {
  id: string;
  rfq_id: string;
  date: string;
  type: 'preview' | 'final';
  status: string;
  created_at?: string | null;
  name: string;
}

function normalizeComparisonRuns(payload: unknown): ComparisonRunRow[] {
  const raw = payload && typeof payload === 'object' ? (payload as { data?: unknown }) : null;
  const list = Array.isArray(raw?.data) ? raw?.data : [];
  return list.map((item: unknown, index: number) => {
    if (item === null || typeof item !== 'object' || Array.isArray(item)) {
      throw new Error(`Invalid comparison run row at index ${index}: expected object`);
    }
    const row = item as Record<string, unknown>;
    const id = row.id ?? row.run_id ?? row.runId;
    if (id === null || id === undefined || String(id).trim() === '') {
      throw new Error(`Missing id for comparison row at index ${index}: ${JSON.stringify(row)}`);
    }
    return {
      id: String(id),
      rfq_id: String(row.rfq_id ?? ''),
      name: String(row.name ?? 'Comparison Run'),
      date:
        row.created_at !== undefined && row.created_at !== null
          ? String(row.created_at)
          : String(row.createdAt ?? ''),
      type: row.is_preview === false || row.mode === 'final' ? 'final' : 'preview',
      status: String(row.status ?? 'generated'),
      created_at: row.created_at !== undefined && row.created_at !== null ? String(row.created_at) : null,
    };
  });
}

export function useComparisonRuns(rfqId: string) {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

  return useQuery({
    queryKey: ['comparison-runs', rfqId],
    queryFn: async (): Promise<ComparisonRunRow[]> => {
      if (useMocks) {
        return getSeedComparisonRunsByRfqId(rfqId).map((r) => ({
          id: r.id,
          rfq_id: r.rfqId,
          date: r.date,
          type: r.type,
          status: r.status,
          name: r.type === 'final' ? 'Final comparison' : 'Preview comparison',
          created_at: null,
        }));
      }

      const { data } = await api.get('/comparison-runs', {
        params: { rfq_id: rfqId },
      });

      return normalizeComparisonRuns(data);
    },
    enabled: Boolean(rfqId),
  });
}
