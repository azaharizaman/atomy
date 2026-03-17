'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';

export interface ProjectHealth {
  projectId: string;
  overallScore?: number;
  labor?: { actualHours?: number; healthPercentage?: number };
  expense?: { healthPercentage?: number };
  timeline?: { completionPercentage?: number; totalMilestones?: number; completedMilestones?: number };
}

function normalizeHealth(payload: any): ProjectHealth {
  let raw = payload as Record<string, unknown> | null | undefined;
  while (raw?.data) raw = raw.data as Record<string, unknown>;

  return {
    projectId: String(raw?.project_id ?? raw?.projectId ?? ''),
    overallScore: raw?.overall_score as number | undefined,
    labor: raw?.labor as any,
    expense: raw?.expense as any,
    timeline: raw?.timeline as any,
  };
}

export function useProjectHealth(projectId: string) {
  return useQuery({
    queryKey: ['projects', projectId, 'health'],
    queryFn: async (): Promise<ProjectHealth> => {
      const { data } = await api.get(`/projects/${encodeURIComponent(projectId)}/health`);
      return normalizeHealth(data);
    },
  });
}

