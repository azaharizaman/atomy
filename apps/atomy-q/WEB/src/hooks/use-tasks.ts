'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';

export interface TaskListItem {
  id: string;
  title: string;
  status: string;
  dueDate?: string | null;
  projectId?: string | null;
}

export interface UseTasksParams {
  assignee_id?: string;
  status?: string;
  /** When false, skips fetch (e.g. Tasks feature off on API). */
  enabled?: boolean;
}

function normalizeTasksPayload(payload: unknown): TaskListItem[] {
  const raw = payload as Record<string, unknown> | null | undefined;
  const data = raw?.data ?? raw;
  const list = Array.isArray(data) ? data : (data as Record<string, unknown>)?.data;
  if (!Array.isArray(list)) return [];
  return list.map((item: Record<string, unknown>) => ({
    id: String(item?.id ?? ''),
    title: String(item?.title ?? 'Task'),
    status: String(item?.status ?? 'pending'),
    dueDate: item?.due_date ? String(item.due_date) : null,
    projectId: (item?.project_id as string) ?? null,
  })).filter((t) => t.id);
}

export function useTasks(params: UseTasksParams = {}) {
  const { enabled: enabledParam, assignee_id, status, ...rest } = params;
  const enabled = enabledParam ?? true;
  return useQuery({
    queryKey: ['tasks', { assignee_id, status, ...rest }],
    queryFn: async (): Promise<TaskListItem[]> => {
      const apiParams: Record<string, string> = {};
      if (assignee_id) apiParams.assignee_id = assignee_id;
      if (status) apiParams.status = status;
      const { data } = await api.get('/tasks', { params: apiParams });
      const items = normalizeTasksPayload(data);
      if (status) {
        return items.filter((t) => t.status === status);
      }
      return items;
    },
    enabled,
  });
}
