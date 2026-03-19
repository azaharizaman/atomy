'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';

export type ProjectAclRole = 'owner' | 'admin' | 'editor' | 'viewer';

export interface ProjectAclEntry {
  userId: string;
  role: ProjectAclRole;
}

function normalizeProjectAcl(payload: unknown): ProjectAclEntry[] {
  const obj = payload && typeof payload === 'object' ? (payload as Record<string, unknown>) : null;
  const data = obj?.data && typeof obj.data === 'object' ? (obj.data as Record<string, unknown>) : obj;
  const roles = (data?.roles ?? data?.data) as unknown;
  if (!Array.isArray(roles)) return [];

  return roles
    .filter((r): r is Record<string, unknown> => !!r && typeof r === 'object' && !Array.isArray(r))
    .map((r) => {
      const raw = String(r.role ?? '').toLowerCase().trim();
      const role = raw === 'manager' ? 'admin' : raw === 'contributor' ? 'editor' : raw === 'client_stakeholder' ? 'viewer' : raw;
      return {
        userId: String(r.user_id ?? r.userId ?? ''),
        role: (role as ProjectAclRole) || 'viewer',
      };
    })
    .filter((r) => r.userId.trim() !== '');
}

export function useProjectAcl(projectId: string) {
  return useQuery({
    queryKey: ['projects', projectId, 'acl'],
    queryFn: async (): Promise<ProjectAclEntry[]> => {
      const { data } = await api.get(`/projects/${encodeURIComponent(projectId)}/acl`);
      return normalizeProjectAcl(data);
    },
  });
}

