'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { fetchLiveOrFail } from '@/lib/api-live';
import { formatCurrencyValue } from '@/lib/format-currency';
import { type RfqStatus, isValidRfqStatus } from '@/hooks/use-rfqs';
import { isObject, toText, unwrapResponse } from '@/hooks/normalize-utils';

export interface RfqDetail {
  id: string;
  displayIdentifier: string;
  title: string;
  description?: string | null;
  status: RfqStatus;
  rfq_number?: string;
  vendorsCount?: number;
  quotesCount?: number;
  estValue?: string;
  currency?: string;
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

function parseMaybeNumber(value: unknown): number | undefined {
  if (value === null || value === undefined) return undefined;
  if (typeof value === 'string' && value.trim() === '') return undefined;
  const n = Number(value);
  if (!Number.isFinite(n)) {
    throw new Error('RFQ detail payload has an invalid numeric field.');
  }
  return n;
}

function normalizeRfq(payload: unknown): RfqDetail {
  let raw = unwrapResponse(payload);
  if (isObject(raw) && !('id' in raw) && isObject(raw.data)) {
    raw = raw.data;
  }

  if (!isObject(raw)) {
    throw new Error('RFQ detail payload must be an object.');
  }

  const id = toText(raw.id ?? raw.rfqId);
  if (id === null) {
    throw new Error('RFQ detail payload is missing id.');
  }

  const title = toText(raw.title ?? raw.name);
  if (title === null) {
    throw new Error(`RFQ "${id}" is missing title.`);
  }

  const rawStatus = raw.status;
  if (!isValidRfqStatus(rawStatus)) {
    throw new Error('We found this RFQ, but its status data is incomplete.');
  }

  const est = raw.estValue ?? raw.estimated_value ?? raw.estimatedValue;
  const currency = toText(raw.currency ?? raw.tenant_currency ?? raw.currency_code) ?? 'USD';
  const rfqNumber = raw.rfq_number != null ? String(raw.rfq_number) : undefined;
  return {
    id,
    displayIdentifier: toText(raw.display_identifier ?? raw.displayIdentifier) ?? rfqNumber ?? title,
    title,
    description:
      raw.description === null || raw.description === undefined
        ? null
        : (() => {
            const s = String(raw.description).trim();
            return s === '' ? null : s;
          })(),
    status: rawStatus,
    rfq_number: rfqNumber,
    vendorsCount: parseMaybeNumber(raw.vendorsCount ?? raw.vendors_count),
    quotesCount: parseMaybeNumber(raw.quotesCount ?? raw.quotes_count),
    estValue: formatCurrencyValue(typeof est === 'number' ? est : toText(est), currency),
    currency,
    savings: raw.savings != null ? String(raw.savings) : undefined,
    projectId: (raw.project_id ?? raw.projectId) as string | null | undefined,
    projectName: (raw.project_name ?? raw.projectName) as string | null | undefined,
    category: raw.category != null ? String(raw.category) : null,
    submission_deadline: (raw.submission_deadline as string | null | undefined) ?? null,
    closing_date: (raw.closing_date as string | null | undefined) ?? null,
    expected_award_at: (raw.expected_award_at as string | null | undefined) ?? null,
    technical_review_due_at: (raw.technical_review_due_at as string | null | undefined) ?? null,
    financial_review_due_at: (raw.financial_review_due_at as string | null | undefined) ?? null,
  };
}

export function useRfq(rfqId: string, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: ['rfqs', rfqId],
    enabled: Boolean(rfqId) && options?.enabled !== false,
    queryFn: async () => {
      const data = await fetchLiveOrFail<{ data: RfqDetail }>(`/rfqs/${encodeURIComponent(rfqId)}`);

      if (data === undefined) {
        throw new Error('We found this RFQ, but its record data could not be loaded.');
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
