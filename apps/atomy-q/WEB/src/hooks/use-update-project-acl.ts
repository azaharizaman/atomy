'use client';

import { useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';
import type { ProjectAclEntry, ProjectAclRole } from '@/hooks/use-project-acl';

export interface UpdateProjectAclPayload {
  roles: Array<{ user_id: string; role: ProjectAclRole }>;
}

function toApiPayload(entries: ProjectAclEntry[]): UpdateProjectAclPayload {
  return {
    roles: entries.map((e) => ({
      user_id: e.userId,
      role: e.role,
    })),
  };
}

export function useUpdateProjectAcl(projectId: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (entries: ProjectAclEntry[]): Promise<ProjectAclEntry[]> => {
      const { data } = await api.put(`/projects/${encodeURIComponent(projectId)}/acl`, toApiPayload(entries));
      const raw = (data?.data ?? data) as Record<string, unknown> | null;
      const roles = (raw?.roles ?? raw?.data) as unknown;
      if (!Array.isArray(roles)) {
        throw new Error('Invalid ACL payload: missing roles');
      }

      return roles
        .filter((r): r is Record<string, unknown> => !!r && typeof r === 'object' && !Array.isArray(r))
        .map((r) => ({
          userId: String(r.user_id ?? ''),
          role: String(r.role ?? 'viewer') as ProjectAclRole,
        }))
        .filter((r) => r.userId.trim() !== '');
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['projects', projectId, 'acl'] });
    },
  });
}

