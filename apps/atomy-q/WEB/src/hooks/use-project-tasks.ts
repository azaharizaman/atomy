'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';

export interface ProjectTaskListItem {
  id: string;
  title?: string;
  status?: string;
  dueDate?: string;
}

function normalize(payload: any): ProjectTaskListItem[] {
  const list = (Array.isArray(payload) ? payload : payload?.data) ?? [];
  if (!Array.isArray(list)) return [];
  return list
    .map((t: any) => ({
      id: String(t.id ?? ''),
      title: t.title,
      status: t.status,
      dueDate: t.due_date ?? t.dueDate,
    }))
    .filter((x) => x.id);
}

export function useProjectTasks(projectId: string) {
  return useQuery({
    queryKey: ['projects', projectId, 'tasks'],
    queryFn: async () => {
      const { data } = await api.get(`/projects/${encodeURIComponent(projectId)}/tasks`);
      return normalize(data);
    },
  });
}

