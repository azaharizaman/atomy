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
  let current: unknown = payload;
  while (current != null && typeof current === 'object' && !Array.isArray(current)) {
    const obj = current as Record<string, unknown>;
    if (Object.prototype.hasOwnProperty.call(obj, 'data')) {
      current = obj.data;
    } else {
      break;
    }
  }
  const list = Array.isArray(current) ? current : [];
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

export function useProjectRfqs(projectId: string, options?: { enabled?: boolean }) {
  const enabled = (options?.enabled ?? true) && projectId !== '';
  return useQuery({
    queryKey: ['projects', projectId, 'rfqs'],
    queryFn: async () => {
      const { data } = await api.get(`/projects/${encodeURIComponent(projectId)}/rfqs`);
      return normalize(data);
    },
    enabled,
  });
}

