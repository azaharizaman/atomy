'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';

export const RFQ_STATUSES = {
  ACTIVE: 'active',
  CLOSED: 'closed',
  AWARDED: 'awarded',
  ARCHIVED: 'archived',
  DRAFT: 'draft',
  PENDING: 'pending',
} as const;

export type RfqStatus = (typeof RFQ_STATUSES)[keyof typeof RFQ_STATUSES];

export const isValidRfqStatus = (status: unknown): status is RfqStatus =>
  Object.values(RFQ_STATUSES).includes(status as RfqStatus);

export interface RfqListItem {
  id: string;
  title: string;
  status: RfqStatus;
  owner?: { name?: string; email?: string };
  deadline?: string;
  category?: string;
  estValue?: string;
  savings?: string;
  vendorsCount?: number;
  quotesCount?: number;
  projectId?: string | null;
}

export interface UseRfqsParams {
  q?: string;
  status?: string;
  owner?: string;
  category?: string;
  page?: number;
  projectId?: string;
}

function normalizeRfqsPayload(payload: unknown): RfqListItem[] {
  const asArray = (value: unknown): unknown[] | null => (Array.isArray(value) ? value : null);

  const obj = payload && typeof payload === 'object' ? (payload as Record<string, unknown>) : null;
  const objData = obj?.data;
  const objDataObj = objData && typeof objData === 'object' ? (objData as Record<string, unknown>) : null;

  // common shapes: [] or { data: [] } or { data: { data: [] } }
  const list =
    asArray(payload) ??
    asArray(objData) ??
    asArray(objDataObj?.data) ??
    [];

  if (list.length === 0) return [];

  return list.map((raw: unknown) => {
    const r =
      raw != null && typeof raw === 'object' && !Array.isArray(raw) ? (raw as Record<string, unknown>) : {};
    const ownerRaw = r.owner;
    const owner =
      ownerRaw != null && typeof ownerRaw === 'object' && !Array.isArray(ownerRaw)
        ? (ownerRaw as Record<string, unknown>)
        : null;
    const vendorsNum = Number(r.vendorsCount ?? r.vendors_count);
    const quotesNum = Number(r.quotesCount ?? r.quotes_count);

    return {
      id: String(r.id ?? r.rfqId ?? r.code ?? ''),
      title: String(r.title ?? r.name ?? 'Untitled'),
      status: isValidRfqStatus(r.status) ? r.status : RFQ_STATUSES.ACTIVE,
      owner: owner
        ? { name: String(owner.name ?? ''), email: String(owner.email ?? '') }
        : r.owner_name != null || r.owner_email != null
          ? { name: String(r.owner_name ?? ''), email: String(r.owner_email ?? '') }
          : undefined,
      deadline: (r.deadline ?? r.submissionDeadline ?? r.deadlineLabel) as string | undefined,
      category: r.category as string | undefined,
      estValue: (r.estValue ?? r.estimated_value ?? r.estimatedValue) as string | undefined,
      savings: r.savings as string | undefined,
      vendorsCount: Number.isNaN(vendorsNum) ? undefined : vendorsNum,
      quotesCount: Number.isNaN(quotesNum) ? undefined : quotesNum,
      projectId: (r.project_id ?? r.projectId ?? null) as string | null,
    };
  });
}

export function useRfqs(params: UseRfqsParams) {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

  return useQuery({
    queryKey: ['rfqs', params],
    queryFn: async (): Promise<RfqListItem[]> => {
      // Prefer live API when mocks are disabled and API is reachable,
      // but always fall back to local seed data on failures or when mocks are enabled.
      if (!useMocks) {
        try {
          const apiParams: Record<string, string | number | undefined> = { ...params };
          if (params.projectId) apiParams.project_id = params.projectId;
          const { data } = await api.get('/rfqs', { params: apiParams });
          const items = normalizeRfqsPayload(data).filter((x) => x.id);
          if (items.length > 0) {
            return items;
          }
        } catch {
          // ignore and fall through to seed data
        }
      }

      const { getSeedRfqListItems } = await import('@/data/seed');
      const { items } = getSeedRfqListItems(params);
      return items;
    },
  });
}

