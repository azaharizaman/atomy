'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';

export interface ProjectTaskListItem {
  id: string;
  title?: string;
  status?: string;
  dueDate?: string;
}

function normalize(payload: unknown): ProjectTaskListItem[] {
  const obj = payload && typeof payload === 'object' ? (payload as Record<string, unknown>) : null;
  const list = (Array.isArray(payload) ? payload : obj?.data) ?? [];
  if (!Array.isArray(list)) return [];
  return list
    .map((t: unknown) => {
      const row = t as Record<string, unknown>;
      return {
        id: String(row.id ?? ''),
        title: row.title as string | undefined,
        status: row.status as string | undefined,
        dueDate: (row.due_date ?? row.dueDate) as string | undefined,
      };
    })
    .filter((x) => x.id);
}

export function useProjectTasks(projectId: string, options?: { enabled?: boolean }) {
  const enabled = (options?.enabled ?? true) && projectId !== '';
  return useQuery({
    queryKey: ['projects', projectId, 'tasks'],
    queryFn: async () => {
      const { data } = await api.get(`/projects/${encodeURIComponent(projectId)}/tasks`);
      return normalize(data);
    },
    enabled,
  });
}

