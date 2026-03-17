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

  const laborRaw = (raw?.labor ?? {}) as any;
  const expenseRaw = (raw?.expense ?? {}) as any;
  const timelineRaw = (raw?.timeline ?? {}) as any;

  return {
    projectId: String(raw?.project_id ?? raw?.projectId ?? ''),
    overallScore: (raw?.overall_score ?? (raw as any)?.overallScore) as number | undefined,
    labor: {
      actualHours: laborRaw.actual_hours ?? laborRaw.actualHours,
      healthPercentage: laborRaw.health_percentage ?? laborRaw.healthPercentage,
    },
    expense: {
      healthPercentage: expenseRaw.health_percentage ?? expenseRaw.healthPercentage,
    },
    timeline: {
      completionPercentage: timelineRaw.completion_percentage ?? timelineRaw.completionPercentage,
      totalMilestones: timelineRaw.total_milestones ?? timelineRaw.totalMilestones,
      completedMilestones: timelineRaw.completed_milestones ?? timelineRaw.completedMilestones,
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

