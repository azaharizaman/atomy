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
        return [
          { id: 'RFQ-2401', title: 'Server Infrastructure Refresh', owner: { name: 'Alex Kumar' }, status: 'active', deadline: '2026-04-15', estValue: '$1,200,000', category: 'IT Hardware', vendorsCount: 5, quotesCount: 8, savings: '12%' },
          { id: 'RFQ-2402', title: 'Office Furniture Q2', owner: { name: 'Sarah Chen' }, status: 'pending', deadline: '2026-03-28', estValue: '$84,500', category: 'Facilities', vendorsCount: 3, quotesCount: 3, savings: '—' },
          { id: 'RFQ-2403', title: 'Cloud Software Licenses', owner: { name: 'Marcus Webb' }, status: 'active', deadline: '2026-04-02', estValue: '$340,000', category: 'Software', vendorsCount: 4, quotesCount: 6, savings: '8%' },
          { id: 'RFQ-2404', title: 'Network Security Audit', owner: { name: 'Priya Nair' }, status: 'awarded', deadline: '2026-03-10', estValue: '$95,000', category: 'Security', vendorsCount: 2, quotesCount: 2, savings: '5%' },
          { id: 'RFQ-2405', title: 'Marketing Print Materials', owner: { name: 'James Okonkwo' }, status: 'draft', deadline: '2026-05-01', estValue: '$12,000', category: 'Marketing', vendorsCount: 0, quotesCount: 0, savings: '—' },
          { id: 'RFQ-2406', title: 'Fleet Vehicle Leasing', owner: { name: 'Sarah Chen' }, status: 'closed', deadline: '2026-02-28', estValue: '$480,000', category: 'Transport', vendorsCount: 6, quotesCount: 6, savings: '3%' },
          { id: 'RFQ-2407', title: 'Catering Services Contract', owner: { name: 'Alex Kumar' }, status: 'active', deadline: '2026-04-20', estValue: '$72,000', category: 'Facilities', vendorsCount: 4, quotesCount: 5, savings: '14%' },
          { id: 'RFQ-2408', title: 'IT Support Annual', owner: { name: 'Marcus Webb' }, status: 'pending', deadline: '2026-03-31', estValue: '$220,000', category: 'IT Services', vendorsCount: 2, quotesCount: 1, savings: '—' },
        ];
      }

      const { data } = await api.get('/rfqs', { params });
      return normalizeRfqsPayload(data).filter((x) => x.id);
    },
  });
}

