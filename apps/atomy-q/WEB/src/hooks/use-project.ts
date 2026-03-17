'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';

export interface ProjectDetail {
  id: string;
  name: string;
  status?: string;
  clientId?: string;
  startDate?: string;
  endDate?: string;
  budgetType?: string;
  completionPercentage?: number;
}

function normalizeProject(payload: any): ProjectDetail {
  let raw = payload as Record<string, unknown> | null | undefined;
  while (raw?.data) raw = raw.data as Record<string, unknown>;

  return {
    id: String(raw?.id ?? ''),
    name: String(raw?.name ?? raw?.title ?? 'Project'),
    status: raw?.status ? String(raw.status) : undefined,
    clientId: (raw?.client_id ?? raw?.clientId) as string | undefined,
    startDate: (raw?.start_date ?? raw?.startDate) as string | undefined,
    endDate: (raw?.end_date ?? raw?.endDate) as string | undefined,
    budgetType: (raw?.budget_type ?? raw?.budgetType) as string | undefined,
    completionPercentage: (raw?.completion_percentage ?? raw?.completionPercentage) as number | undefined,
  };
}

export function useProject(projectId: string) {
  return useQuery({
    queryKey: ['projects', projectId],
    queryFn: async (): Promise<ProjectDetail> => {
      const { data } = await api.get(`/projects/${encodeURIComponent(projectId)}`);
      return normalizeProject(data);
    },
  });
}

