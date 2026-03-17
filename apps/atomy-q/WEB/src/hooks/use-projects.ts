'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';

export interface ProjectListItem {
  id: string;
  name: string;
  status?: string;
  clientId?: string;
  clientName?: string;
  startDate?: string;
  endDate?: string;
}

function normalizeProjectsPayload(payload: any): ProjectListItem[] {
  const asArray = (value: unknown): unknown[] | null => (Array.isArray(value) ? value : null);

  const list = asArray(payload) ?? asArray(payload?.data) ?? asArray(payload?.data?.data) ?? [];
  if (list.length === 0) return [];

  return list
    .filter((raw: any) => raw && typeof raw === 'object')
    .map((raw: any) => ({
      id: String(raw.id ?? ''),
      name: String(raw.name ?? raw.title ?? 'Untitled'),
      status: raw.status ? String(raw.status) : undefined,
      clientId: raw.client_id ?? raw.clientId,
      clientName: raw.client_name ?? raw.clientName,
      startDate: raw.start_date ?? raw.startDate,
      endDate: raw.end_date ?? raw.endDate,
    }))
    .filter((p) => p.id);
}

export function useProjects() {
  return useQuery({
    queryKey: ['projects'],
    queryFn: async (): Promise<ProjectListItem[]> => {
      const { data } = await api.get('/projects');
      return normalizeProjectsPayload(data);
    },
  });
}

