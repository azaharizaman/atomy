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
}

export interface UseRfqsParams {
  q?: string;
  status?: string;
  owner?: string;
  category?: string;
  page?: number;
}

function normalizeRfqsPayload(payload: any): RfqListItem[] {
  const asArray = (value: unknown): unknown[] | null => (Array.isArray(value) ? value : null);

  // common shapes: [] or { data: [] } or { data: { data: [] } }
  const list =
    asArray(payload) ??
    asArray(payload?.data) ??
    asArray(payload?.data?.data) ??
    [];

  if (list.length === 0) return [];

  return list.map((raw: any) => ({
    id: String(raw.id ?? raw.rfqId ?? raw.code ?? ''),
    title: String(raw.title ?? raw.name ?? 'Untitled'),
    status: isValidRfqStatus(raw.status) ? raw.status : RFQ_STATUSES.ACTIVE,
    owner: raw.owner
      ? { name: raw.owner.name, email: raw.owner.email }
      : raw.owner_name || raw.owner_email
        ? { name: raw.owner_name, email: raw.owner_email }
        : undefined,
    deadline: raw.deadline ?? raw.submissionDeadline ?? raw.deadlineLabel,
    category: raw.category,
    estValue: raw.estValue ?? raw.estimated_value ?? raw.estimatedValue,
    savings: raw.savings,
    vendorsCount: raw.vendorsCount ?? raw.vendors_count,
    quotesCount: raw.quotesCount ?? raw.quotes_count,
  }));
}

export function useRfqs(params: UseRfqsParams) {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

  return useQuery({
    queryKey: ['rfqs', params],
    queryFn: async (): Promise<RfqListItem[]> => {
      if (useMocks) {
        const { getSeedRfqListItems } = await import('@/data/seed');
        const { items } = getSeedRfqListItems(params);
        return items;
      }

      const { data } = await api.get('/rfqs', { params });
      return normalizeRfqsPayload(data).filter((x) => x.id);
    },
  });
}

