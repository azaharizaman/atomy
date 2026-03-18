'use client';

import { useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';

export interface UpdateProjectPayload {
  name?: string;
  client_id?: string;
  start_date?: string;
  end_date?: string;
  project_manager_id?: string;
  budget_type?: string;
  completion_percentage?: number;
}

export function useUpdateProject(projectId: string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (payload: UpdateProjectPayload) => {
      const { data } = await api.put(`/projects/${encodeURIComponent(projectId)}`, payload);
      return data?.data ?? data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['projects'] });
      queryClient.invalidateQueries({ queryKey: ['projects', projectId] });
    },
  });
}
