'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';

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
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';
  const enabled = (options?.enabled ?? true) && Boolean(rfqId) && !useMocks;

  const query = useQuery({
    queryKey: ['normalization-conflicts', rfqId],
    queryFn: async (): Promise<ConflictsResponse> => {
      const { data } = await api.get<ConflictsResponse>(
        `/normalization/${encodeURIComponent(rfqId)}/conflicts`,
      );
      return data;
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
      qc.invalidateQueries({ queryKey: ['rfqs', rfqId, 'overview'] });
    },
  });

  const meta = query.data?.meta;
  return {
    ...query,
    conflicts: query.data?.data ?? [],
    hasBlockingIssues: Boolean(meta?.has_blocking_issues),
    blockingIssueCount: Number(meta?.blocking_issue_count ?? 0),
    resolveConflict,
  };
}
