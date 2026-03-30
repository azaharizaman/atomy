'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { getSeedComparisonRunsByRfqId } from '@/data/seed';

export interface ComparisonRunRow {
  id: string;
  rfq_id: string;
  run_id: string;
  date: string;
  type: 'preview' | 'final';
  status: string;
  scoring_model: string;
  created_by: string;
  created_at?: string | null;
}

function normalizeComparisonRuns(payload: unknown): ComparisonRunRow[] {
  const raw = payload && typeof payload === 'object' ? (payload as { data?: unknown }) : null;
  const list = Array.isArray(raw?.data) ? raw?.data : [];
  return list.map((item: unknown, index: number) => {
    if (item === null || typeof item !== 'object' || Array.isArray(item)) {
      throw new Error(`Invalid comparison run row at index ${index}: expected object`);
    }
    const row = item as Record<string, unknown>;
    return {
      id: String(row.id ?? ''),
      rfq_id: String(row.rfq_id ?? ''),
      run_id: String(row.run_id ?? row.runId ?? row.name ?? `RUN-${index + 1}`),
      date:
        row.created_at !== undefined && row.created_at !== null
          ? String(row.created_at)
          : String(row.date ?? ''),
      type: row.is_preview === false || row.mode === 'final' ? 'final' : 'preview',
      status: String(row.status ?? 'generated'),
      scoring_model: String(row.scoring_model ?? row.scoringModel ?? 'v1'),
      created_by: String(row.created_by ?? row.createdBy ?? ''),
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
          run_id: r.runId,
          date: r.date,
          type: r.type,
          status: r.status,
          scoring_model: r.scoringModel,
          created_by: r.createdBy,
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
