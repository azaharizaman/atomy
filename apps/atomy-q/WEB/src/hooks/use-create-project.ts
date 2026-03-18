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
      const requireString = (value: unknown, field: string): string => {
        const out = typeof value === 'string' ? value : value === null || value === undefined ? '' : String(value);
        if (out.trim() === '') {
          throw new Error(`Invalid project payload: missing ${field}`);
        }
        return out;
      };
      return {
        id: requireString(raw?.id, 'id'),
        name: requireString(raw?.name, 'name'),
        status: requireString(raw?.status, 'status'),
        client_id: requireString(raw?.client_id, 'client_id'),
        start_date: requireString(raw?.start_date, 'start_date'),
        end_date: requireString(raw?.end_date, 'end_date'),
      };
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['projects'] });
    },
  });
}
