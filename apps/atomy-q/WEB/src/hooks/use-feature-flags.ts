'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';

export interface TenantFeatureFlags {
  projects: boolean;
  tasks: boolean;
}

function normalizeFeatureFlags(payload: unknown): TenantFeatureFlags {
  const obj = payload && typeof payload === 'object' ? (payload as Record<string, unknown>) : null;
  const data =
    obj?.data && typeof obj.data === 'object' && !Array.isArray(obj.data)
      ? (obj.data as Record<string, unknown>)
      : obj;
  return {
    projects: Boolean(data?.projects),
    tasks: Boolean(data?.tasks),
  };
}

/**
 * Server-driven feature toggles (mirrors API `config('features.*')`).
 * Use for nav visibility, redirects, and skipping queries that would 404 when a feature is off.
 */
export function useFeatureFlags() {
  return useQuery({
    queryKey: ['feature-flags'],
    queryFn: async (): Promise<TenantFeatureFlags> => {
      const { data } = await api.get('/feature-flags');
      return normalizeFeatureFlags(data);
    },
    staleTime: 5 * 60 * 1000,
  });
}
