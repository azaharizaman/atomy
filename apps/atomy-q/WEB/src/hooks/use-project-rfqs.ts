'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';

export interface ProjectRfqListItem {
  id: string;
  rfqNumber?: string;
  title?: string;
  status?: string;
}

function normalize(payload: any): ProjectRfqListItem[] {
  const list = (Array.isArray(payload) ? payload : payload?.data) ?? [];
  if (!Array.isArray(list)) return [];
  return list
    .filter((r: any) => r && typeof r === 'object')
    .map((r: any) => ({
      id: String(r.id ?? ''),
      rfqNumber: r.rfq_number ?? r.rfqNumber,
      title: r.title,
      status: r.status,
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

