'use client';

import { useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';

export interface CreateProjectPayload {
  name: string;
  client_id: string;
  start_date: string;
  end_date: string;
  project_manager_id: string;
}

export interface CreateProjectResult {
  id: string;
  name: string;
  status: string;
  client_id: string;
  start_date: string;
  end_date: string;
}

export function useCreateProject() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (payload: CreateProjectPayload): Promise<CreateProjectResult> => {
      const { data } = await api.post('/projects', payload);
      const raw = data?.data ?? data;
      return {
        id: String(raw?.id ?? ''),
        name: String(raw?.name ?? ''),
        status: String(raw?.status ?? ''),
        client_id: String(raw?.client_id ?? ''),
        start_date: String(raw?.start_date ?? ''),
        end_date: String(raw?.end_date ?? ''),
      };
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['projects'] });
    },
  });
}
