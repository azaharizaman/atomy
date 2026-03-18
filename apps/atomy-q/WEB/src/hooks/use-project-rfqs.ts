'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';

export interface ProjectRfqListItem {
  id: string;
  rfqNumber?: string;
  title?: string;
  status?: string;
}

function normalize(payload: unknown): ProjectRfqListItem[] {
  const obj = payload && typeof payload === 'object' ? (payload as Record<string, unknown>) : null;
  const list = (Array.isArray(payload) ? payload : obj?.data) ?? [];
  if (!Array.isArray(list)) return [];
  return list
    .filter((r: unknown): r is Record<string, unknown> => !!r && typeof r === 'object')
    .map((r) => ({
      id: String(r.id ?? ''),
      rfqNumber: (r.rfq_number ?? r.rfqNumber) as string | undefined,
      title: r.title as string | undefined,
      status: r.status as string | undefined,
    }))
    .filter((x) => x.id);
}

export function useProjectRfqs(projectId: string) {
  return useQuery({
    queryKey: ['projects', projectId, 'rfqs'],
    queryFn: async () => {
      const { data } = await api.get(`/projects/${encodeURIComponent(projectId)}/rfqs`);
      return normalize(data);
    },
  });
}

