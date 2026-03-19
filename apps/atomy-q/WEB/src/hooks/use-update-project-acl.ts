'use client';

import { useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';
import type { ProjectAclEntry, ProjectAclRole } from '@/hooks/use-project-acl';

const VALID_PROJECT_ACL_ROLES: ReadonlySet<ProjectAclRole> = new Set<ProjectAclRole>(['owner', 'admin', 'editor', 'viewer']);

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
        .map((r) => {
          const roleRaw = String(r.role ?? 'viewer').toLowerCase().trim();
          const candidate =
            roleRaw === 'manager'
              ? 'admin'
              : roleRaw === 'contributor'
              ? 'editor'
              : roleRaw === 'client_stakeholder'
              ? 'viewer'
              : roleRaw;
          if (!VALID_PROJECT_ACL_ROLES.has(candidate as ProjectAclRole)) {
            throw new Error(`Invalid ACL role received: ${candidate}`);
          }
          const role = candidate as ProjectAclRole;

          return {
            userId: String(r.user_id ?? ''),
            role,
          };
        })
        .filter((r) => r.userId.trim() !== '');
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['projects', projectId, 'acl'] });
    },
  });
}

