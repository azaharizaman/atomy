'use client';

import { useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';

export const TASK_STATUSES = ['pending', 'in_progress', 'completed', 'cancelled'] as const;
export type TaskStatusValue = (typeof TASK_STATUSES)[number];

export function useUpdateTaskStatus(taskId: string | null) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (status: TaskStatusValue) => {
      if (!taskId) throw new Error('No task id');
      const { data } = await api.patch(`/tasks/${encodeURIComponent(taskId)}/status`, { status });
      return data?.data ?? data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['tasks'] });
      if (taskId) queryClient.invalidateQueries({ queryKey: ['tasks', taskId] });
    },
  });
}
