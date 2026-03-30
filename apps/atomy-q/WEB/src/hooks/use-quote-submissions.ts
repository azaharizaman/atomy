'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { getSeedQuotesByRfqId } from '@/data/seed';

export interface QuoteSubmissionRow {
  id: string;
  rfq_id: string;
  vendor_id: string;
  vendor_name: string;
  file_name: string;
  status: string;
  confidence: string;
  uploaded_at: string | null;
  blocking_issue_count: number;
  original_filename?: string | null;
}

function normalizeQuoteSubmissionRows(payload: unknown): QuoteSubmissionRow[] {
  const raw = payload && typeof payload === 'object' ? (payload as { data?: unknown }) : null;
  const list = Array.isArray(raw?.data) ? raw?.data : [];
  return list.map((item: unknown, index: number) => {
    if (item === null || typeof item !== 'object' || Array.isArray(item)) {
      throw new Error(`Invalid quote submission row at index ${index}: expected object`);
    }
    const row = item as Record<string, unknown>;
    return {
      id: String(row.id ?? ''),
      rfq_id: String(row.rfq_id ?? ''),
      vendor_id: String(row.vendor_id ?? ''),
      vendor_name: String(row.vendor_name ?? ''),
      file_name:
        String(row.original_filename ?? row.file_name ?? row.file_path ?? '').trim() || `Quote ${index + 1}`,
      status: String(row.status ?? 'uploaded'),
      confidence:
        row.confidence === null || row.confidence === undefined
          ? 'medium'
          : Number(row.confidence) >= 90
            ? 'high'
            : Number(row.confidence) >= 70
              ? 'medium'
              : 'low',
      uploaded_at: row.submitted_at !== undefined && row.submitted_at !== null ? String(row.submitted_at) : null,
      blocking_issue_count: Number(row.blocking_issue_count ?? 0),
      original_filename: row.original_filename !== undefined && row.original_filename !== null ? String(row.original_filename) : null,
    };
  });
}

export function useQuoteSubmissions(rfqId: string) {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

  return useQuery({
    queryKey: ['quote-submissions', 'list', rfqId],
    queryFn: async (): Promise<QuoteSubmissionRow[]> => {
      if (useMocks) {
        return getSeedQuotesByRfqId(rfqId).map((q) => ({
          id: q.id,
          rfq_id: q.rfqId,
          vendor_id: q.vendorId,
          vendor_name: q.vendorName,
          file_name: q.fileName,
          status: q.status,
          confidence: q.confidence,
          uploaded_at: q.uploadedAt,
          blocking_issue_count: q.status === 'error' ? 1 : 0,
          original_filename: q.fileName,
        }));
      }

      const { data } = await api.get('/quote-submissions', {
        params: { rfq_id: rfqId },
      });

      return normalizeQuoteSubmissionRows(data);
    },
    enabled: Boolean(rfqId),
  });
}
