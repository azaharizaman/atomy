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

function normalizeProjectsPayload(payload: unknown): ProjectListItem[] {
  const asArray = (value: unknown): unknown[] | null => (Array.isArray(value) ? value : null);

  const obj = payload && typeof payload === 'object' ? (payload as Record<string, unknown>) : null;
  const objData = obj?.data;
  const objDataObj = objData && typeof objData === 'object' ? (objData as Record<string, unknown>) : null;

  const list =
    asArray(payload) ??
    asArray(objData) ??
    asArray(objDataObj?.data) ??
    [];
  if (list.length === 0) return [];

  return list
    .filter((raw: unknown): raw is Record<string, unknown> => !!raw && typeof raw === 'object')
    .map((raw) => ({
      id: String(raw.id ?? ''),
      name: String(raw.name ?? raw.title ?? 'Untitled'),
      status: raw.status ? String(raw.status) : undefined,
      clientId: (raw.client_id ?? raw.clientId) as string | undefined,
      clientName: (raw.client_name ?? raw.clientName) as string | undefined,
      startDate: (raw.start_date ?? raw.startDate) as string | undefined,
      endDate: (raw.end_date ?? raw.endDate) as string | undefined,
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

