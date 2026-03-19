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

function toNum(value: unknown): number | undefined {
  if (value === null || value === undefined) return undefined;
  if (typeof value === 'string' && value.trim() === '') return undefined;
  const n = Number(value);
  return Number.isFinite(n) ? n : undefined;
}

function normalizeHealth(payload: unknown): ProjectHealth {
  let raw = payload as Record<string, unknown> | null | undefined;
  while (raw?.data) raw = raw.data as Record<string, unknown>;

  const laborRaw = (raw?.labor ?? {}) as Record<string, unknown>;
  const expenseRaw = (raw?.expense ?? {}) as Record<string, unknown>;
  const timelineRaw = (raw?.timeline ?? {}) as Record<string, unknown>;

  return {
    projectId: String(raw?.project_id ?? raw?.projectId ?? ''),
    overallScore: toNum(raw?.overall_score ?? (raw as Record<string, unknown>)?.overallScore),
    labor: {
      actualHours: toNum(laborRaw.actual_hours ?? laborRaw.actualHours),
      healthPercentage: toNum(laborRaw.health_percentage ?? laborRaw.healthPercentage),
    },
    expense: {
      healthPercentage: toNum(expenseRaw.health_percentage ?? expenseRaw.healthPercentage),
    },
    timeline: {
      completionPercentage: toNum(timelineRaw.completion_percentage ?? timelineRaw.completionPercentage),
      totalMilestones: toNum(timelineRaw.total_milestones ?? timelineRaw.totalMilestones),
      completedMilestones: toNum(timelineRaw.completed_milestones ?? timelineRaw.completedMilestones),
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

