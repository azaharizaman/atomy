'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { fetchLiveOrFail } from '@/lib/api-live';
import { type RfqStatus, isValidRfqStatus, RFQ_STATUSES } from '@/hooks/use-rfqs';

export interface RfqDetail {
  id: string;
  title: string;
  description?: string | null;
  status: RfqStatus;
  rfq_number?: string;
  vendorsCount?: number;
  quotesCount?: number;
  estValue?: string;
  savings?: string;
  projectId?: string | null;
  projectName?: string | null;
  category?: string | null;
  submission_deadline?: string | null;
  closing_date?: string | null;
  expected_award_at?: string | null;
  technical_review_due_at?: string | null;
  financial_review_due_at?: string | null;
}

/** Body for PUT /rfqs/:id (validated keys only). */
export interface RfqUpdateBody {
  title?: string;
  description?: string | null;
  category?: string | null;
  submission_deadline?: string | null;
  closing_date?: string | null;
  expected_award_at?: string | null;
  technical_review_due_at?: string | null;
  financial_review_due_at?: string | null;
}

function normalizeRfq(payload: unknown): RfqDetail {
  let raw = payload as Record<string, unknown> | null | undefined;
  while (raw?.data) {
    raw = raw.data as Record<string, unknown>;
  }
  const rawStatus = raw?.status;
  const status: RfqStatus = isValidRfqStatus(rawStatus) ? rawStatus : RFQ_STATUSES.ACTIVE;
  const est = raw?.estValue ?? raw?.estimated_value ?? raw?.estimatedValue;
  return {
    id: String(raw?.id ?? raw?.rfqId ?? ''),
    title: String(raw?.title ?? raw?.name ?? 'Requisition'),
    description:
      raw?.description === null || raw?.description === undefined
        ? null
        : (() => {
            const s = String(raw.description).trim();
            return s === '' ? null : s;
          })(),
    status,
    rfq_number: raw?.rfq_number != null ? String(raw.rfq_number) : undefined,
    vendorsCount: (raw?.vendorsCount ?? raw?.vendors_count) as number | undefined,
    quotesCount: (raw?.quotesCount ?? raw?.quotes_count) as number | undefined,
    estValue: est !== null && est !== undefined ? String(est) : undefined,
    savings: raw?.savings != null ? String(raw.savings) : undefined,
    projectId: (raw?.project_id ?? raw?.projectId) as string | null | undefined,
    projectName: (raw?.project_name ?? raw?.projectName) as string | null | undefined,
    category: raw?.category != null ? String(raw.category) : null,
    submission_deadline: (raw?.submission_deadline as string | null | undefined) ?? null,
    closing_date: (raw?.closing_date as string | null | undefined) ?? null,
    expected_award_at: (raw?.expected_award_at as string | null | undefined) ?? null,
    technical_review_due_at: (raw?.technical_review_due_at as string | null | undefined) ?? null,
    financial_review_due_at: (raw?.financial_review_due_at as string | null | undefined) ?? null,
  };
}

export function useRfq(rfqId: string) {
  return useQuery({
    queryKey: ['rfqs', rfqId],
    queryFn: async () => {
      const data = await fetchLiveOrFail<{ data: RfqDetail }>(`/rfqs/${encodeURIComponent(rfqId)}`);

      if (data === undefined) {
        const { getSeedRfqDetail } = await import('@/data/seed');
        const detail = getSeedRfqDetail(rfqId);
        if (!detail) throw new Error('RFQ not found');
        return normalizeRfq(detail);
      }

      return normalizeRfq(data);
    },
  });
}

export function useUpdateRfq(rfqId: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (body: RfqUpdateBody) => {
      const { data } = await api.put(`/rfqs/${encodeURIComponent(rfqId)}`, body);
      return data;
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['rfqs', rfqId] });
      await queryClient.invalidateQueries({ queryKey: ['rfqs', rfqId, 'overview'] });
    },
  });
}

