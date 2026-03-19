'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';

export interface TaskDetail {
  id: string;
  title: string;
  description?: string | null;
  status: string;
  priority?: string;
  dueDate?: string | null;
  projectId?: string | null;
  assigneeIds?: string[];
  completedAt?: string | null;
}

function normalizeTask(payload: unknown): TaskDetail {
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
    title: String(raw?.title ?? 'Task'),
    description: (raw?.description as string) ?? null,
    status: String(raw?.status ?? 'pending'),
    priority: raw?.priority as string | undefined,
    dueDate: (raw?.due_date as string) ?? null,
    projectId: (raw?.project_id as string) ?? null,
    assigneeIds: Array.isArray(raw?.assignee_ids) ? (raw.assignee_ids as string[]) : undefined,
    completedAt: (raw?.completed_at as string) ?? null,
  };
}

export function useTask(taskId: string | null) {
  return useQuery({
    queryKey: ['tasks', taskId],
    queryFn: async (): Promise<TaskDetail> => {
      if (!taskId) throw new Error('No task id');
      const { data } = await api.get(`/tasks/${encodeURIComponent(taskId)}`);
      return normalizeTask(data);
    },
    enabled: !!taskId,
  });
}
