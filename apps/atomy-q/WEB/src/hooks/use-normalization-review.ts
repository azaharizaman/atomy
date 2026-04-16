'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { fetchLiveOrFail } from '@/lib/api-live';
import { api } from '@/lib/api';
import { isObject, toText } from '@/hooks/normalize-utils';

export type NormalizationConflictRow = {
  id: string;
  conflict_type: string;
  resolution: string | null;
  normalization_source_line_id: string;
};

type ConflictsResponse = {
  data: NormalizationConflictRow[];
  meta: {
    rfq_id?: string;
    has_blocking_issues?: boolean;
    blocking_issue_count?: number;
  };
};

function normalizeConflictRow(item: unknown, index: number): NormalizationConflictRow {
  if (!isObject(item)) {
    throw new Error(`Invalid normalization conflict at index ${index}: expected object.`);
  }

  const id = toText(item.id);
  const conflictType = toText(item.conflict_type ?? item.conflictType);
  const normalizationSourceLineId = toText(item.normalization_source_line_id ?? item.normalizationSourceLineId);
  if (id === null || conflictType === null || normalizationSourceLineId === null) {
    throw new Error(`Invalid normalization conflict at index ${index}: missing required fields.`);
  }

  const resolution = item.resolution === null || item.resolution === undefined ? null : toText(item.resolution);

  return {
    id,
    conflict_type: conflictType,
    resolution,
    normalization_source_line_id: normalizationSourceLineId,
  };
}

function parseBlockingIssueCount(value: unknown): number | undefined {
  if (value === null || value === undefined) return undefined;
  if (typeof value === 'string' && value.trim() === '') return undefined;
  const count = Number(value);
  if (!Number.isFinite(count)) {
    throw new Error('Invalid normalization review response: invalid blocking_issue_count.');
  }
  return count;
}

function normalizeConflictsResponse(payload: unknown): ConflictsResponse {
  if (!isObject(payload)) {
    throw new Error('Invalid normalization review response: expected object envelope.');
  }

  if (!Array.isArray(payload.data)) {
    throw new Error('Invalid normalization review response: expected data array.');
  }

  if (!isObject(payload.meta)) {
    throw new Error('Invalid normalization review response: expected meta object.');
  }

  return {
    data: payload.data.map((item, index) => normalizeConflictRow(item, index)),
    meta: {
      rfq_id: toText(payload.meta.rfq_id ?? payload.meta.rfqId) ?? undefined,
      has_blocking_issues:
        typeof payload.meta.has_blocking_issues === 'boolean' ? payload.meta.has_blocking_issues : undefined,
      blocking_issue_count: parseBlockingIssueCount(payload.meta.blocking_issue_count),
    },
  };
}

export function conflictTypeLabel(conflictType: string): string {
  const t = conflictType.toLowerCase();
  if (t.includes('price')) return 'Missing price';
  if (t.includes('currency')) return 'Missing currency';
  if (t.includes('map') || t.includes('ambiguous')) return 'Ambiguous mapping';
  if (t.includes('uom')) return 'Invalid UOM';
  return conflictType.replace(/_/g, ' ');
}

export function useNormalizationReview(rfqId: string, options?: { enabled?: boolean }) {
  const qc = useQueryClient();
  const enabled = (options?.enabled ?? true) && Boolean(rfqId);

  const query = useQuery({
    queryKey: ['normalization-conflicts', rfqId],
    queryFn: async (): Promise<ConflictsResponse> => {
      const data = await fetchLiveOrFail<ConflictsResponse>(`/normalization/${encodeURIComponent(rfqId)}/conflicts`);

      if (data === undefined) {
        throw new Error(`Normalization review unavailable for RFQ "${rfqId}".`);
      }

      return normalizeConflictsResponse(data);
    },
    enabled,
  });

  const resolveConflict = useMutation({
    mutationFn: async (input: { conflictId: string; resolution: string; resolution_data?: Record<string, unknown> }) => {
      const { data } = await api.put(
        `/normalization/conflicts/${encodeURIComponent(input.conflictId)}/resolve`,
        {
          resolution: input.resolution,
          resolution_data: input.resolution_data ?? {},
        },
      );
      return data;
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['normalization-conflicts', rfqId] });
      qc.invalidateQueries({ queryKey: ['quote-submissions', 'list', rfqId] });
      qc.invalidateQueries({ queryKey: ['quote-submissions', rfqId] });
      qc.invalidateQueries({ queryKey: ['rfqs', rfqId, 'overview'] });
    },
  });

  const meta = query.data?.meta;
  const blockingIssueCount = meta?.blocking_issue_count ?? 0;
  return {
    ...query,
    conflicts: query.data?.data ?? [],
    hasBlockingIssues: Boolean(meta?.has_blocking_issues),
    blockingIssueCount,
    resolveConflict,
  };
}
