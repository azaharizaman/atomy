'use client';

import { useQuery } from '@tanstack/react-query';
import { fetchLiveOrFail } from '@/lib/api-live';
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

function requireTextField(value: unknown, fieldName: string, rowIndex: number): string {
  if (value === null || value === undefined) {
    throw new Error(`Invalid comparison run row at index ${rowIndex}: missing ${fieldName}`);
  }

  const text = String(value).trim();
  if (text === '') {
    throw new Error(`Invalid comparison run row at index ${rowIndex}: missing ${fieldName}`);
  }

  return text;
}

function normalizeRunType(row: Record<string, unknown>, index: number): 'preview' | 'final' {
  const typeValue = row.type;
  if (typeValue === 'preview' || typeValue === 'final') {
    return typeValue;
  }

  const modeValue = row.mode;
  if (modeValue === 'preview' || modeValue === 'final') {
    return modeValue;
  }

  const isPreviewValue = row.is_preview;
  if (typeof isPreviewValue === 'boolean') {
    return isPreviewValue ? 'preview' : 'final';
  }

  throw new Error(`Invalid comparison run row at index ${index}: missing type`);
}

function normalizeComparisonRuns(payload: unknown): ComparisonRunRow[] {
  if (payload === null || typeof payload !== 'object' || Array.isArray(payload)) {
    throw new Error('Invalid comparison runs response: expected object envelope with data array.');
  }

  const raw = payload as { data?: unknown };
  if (!Array.isArray(raw.data)) {
    throw new Error('Invalid comparison runs response: expected data array.');
  }

  const list = raw.data;
  return list.map((item: unknown, index: number) => {
    if (item === null || typeof item !== 'object' || Array.isArray(item)) {
      throw new Error(`Invalid comparison run row at index ${index}: expected object`);
    }
    const row = item as Record<string, unknown>;
    const id = requireTextField(row.id ?? row.run_id ?? row.runId, 'id', index);
    const date = requireTextField(row.created_at ?? row.createdAt, 'date', index);
    return {
      id,
      rfq_id: requireTextField(row.rfq_id, 'rfq_id', index),
      name: requireTextField(row.name, 'name', index),
      date,
      type: normalizeRunType(row, index),
      status: requireTextField(row.status, 'status', index),
      created_at: String(row.created_at ?? row.createdAt ?? null),
    };
  });
}

export function useComparisonRuns(rfqId: string) {
  return useQuery({
    queryKey: ['comparison-runs', rfqId],
    queryFn: async (): Promise<ComparisonRunRow[]> => {
      const data = await fetchLiveOrFail<{ data: ComparisonRunRow[] }>('/comparison-runs', {
        params: { rfq_id: rfqId },
      });

      if (data === undefined) {
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

      return normalizeComparisonRuns(data);
    },
    enabled: Boolean(rfqId),
  });
}
