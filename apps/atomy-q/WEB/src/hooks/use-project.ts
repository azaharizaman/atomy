'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';

export interface ProjectDetail {
  id: string;
  name: string;
  status?: string;
  clientId?: string;
  clientName?: string;
  projectManagerId?: string;
  startDate?: string;
  endDate?: string;
  budgetType?: string;
  completionPercentage?: number;
}

function normalizeProject(payload: unknown): ProjectDetail {
  let raw = payload as Record<string, unknown> | null | undefined;
  let depth = 0;
  while (depth < 5 && raw != null && typeof raw.data === 'object' && raw.data !== null && !Array.isArray(raw.data)) {
    const next = raw.data as Record<string, unknown>;
    if (next === raw) break;
    raw = next;
    depth++;
  }

  return {
    id: String(raw?.id ?? ''),
    name: String(raw?.name ?? raw?.title ?? 'Project'),
    status: raw?.status ? String(raw.status) : undefined,
    clientId: (raw?.client_id ?? raw?.clientId) as string | undefined,
    clientName: (raw?.client_name ?? raw?.clientName) as string | undefined,
    projectManagerId: (raw?.project_manager_id ?? raw?.projectManagerId) as string | undefined,
    startDate: (raw?.start_date ?? raw?.startDate) as string | undefined,
    endDate: (raw?.end_date ?? raw?.endDate) as string | undefined,
    budgetType: (raw?.budget_type ?? raw?.budgetType) as string | undefined,
    completionPercentage: (raw?.completion_percentage ?? raw?.completionPercentage) as number | undefined,
  };
}

export function useProject(projectId: string, options?: { enabled?: boolean }) {
  const enabled = (options?.enabled ?? true) && projectId !== '';
  return useQuery({
    queryKey: ['projects', projectId],
    queryFn: async (): Promise<ProjectDetail> => {
      const { data } = await api.get(`/projects/${encodeURIComponent(projectId)}`);
      return normalizeProject(data);
    },
    enabled,
  });
}
