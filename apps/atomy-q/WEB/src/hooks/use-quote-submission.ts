'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';

export interface QuoteSubmissionSummary {
  id: string;
  status?: string;
  blocking_issue_count?: number;
  rfq_id?: string;
  vendor_id?: string;
  vendor_name?: string;
}

function normalizeQuoteSubmission(payload: unknown): QuoteSubmissionSummary {
  const raw = (payload as { data?: Record<string, unknown> })?.data ?? (payload as Record<string, unknown>);
  return {
    id: String(raw?.id ?? ''),
    status: raw?.status !== undefined ? String(raw.status) : undefined,
    blocking_issue_count:
      raw?.blocking_issue_count !== undefined ? Number(raw.blocking_issue_count) : undefined,
    rfq_id: raw?.rfq_id !== undefined ? String(raw.rfq_id) : undefined,
    vendor_id: raw?.vendor_id !== undefined ? String(raw.vendor_id) : undefined,
    vendor_name: raw?.vendor_name !== undefined ? String(raw.vendor_name) : undefined,
  };
}

export function useQuoteSubmission(quoteId: string, options?: { enabled?: boolean }) {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';
  const enabled = (options?.enabled ?? true) && Boolean(quoteId);

  return useQuery({
    queryKey: ['quote-submissions', quoteId],
    queryFn: async (): Promise<QuoteSubmissionSummary> => {
      if (useMocks) {
        return {
          id: quoteId,
          status: 'ready',
          blocking_issue_count: 0,
          vendor_name: 'Vendor',
        };
      }
      const { data } = await api.get(`/quote-submissions/${encodeURIComponent(quoteId)}`);
      return normalizeQuoteSubmission(data);
    },
    enabled: enabled && (useMocks || Boolean(quoteId)),
  });
}
