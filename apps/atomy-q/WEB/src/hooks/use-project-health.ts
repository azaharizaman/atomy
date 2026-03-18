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

function normalizeHealth(payload: unknown): ProjectHealth {
  let raw = payload as Record<string, unknown> | null | undefined;
  while (raw?.data) raw = raw.data as Record<string, unknown>;

  const laborRaw = (raw?.labor ?? {}) as Record<string, unknown>;
  const expenseRaw = (raw?.expense ?? {}) as Record<string, unknown>;
  const timelineRaw = (raw?.timeline ?? {}) as Record<string, unknown>;

  return {
    projectId: String(raw?.project_id ?? raw?.projectId ?? ''),
    overallScore: (raw?.overall_score ?? (raw as Record<string, unknown>)?.overallScore) as number | undefined,
    labor: {
      actualHours: (laborRaw.actual_hours ?? laborRaw.actualHours) as number | undefined,
      healthPercentage: (laborRaw.health_percentage ?? laborRaw.healthPercentage) as number | undefined,
    },
    expense: {
      healthPercentage: (expenseRaw.health_percentage ?? expenseRaw.healthPercentage) as number | undefined,
    },
    timeline: {
      completionPercentage: (timelineRaw.completion_percentage ?? timelineRaw.completionPercentage) as number | undefined,
      totalMilestones: (timelineRaw.total_milestones ?? timelineRaw.totalMilestones) as number | undefined,
      completedMilestones: (timelineRaw.completed_milestones ?? timelineRaw.completedMilestones) as number | undefined,
    },
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

