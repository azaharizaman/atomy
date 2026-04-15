'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { fetchLiveOrFail } from '@/lib/api-live';

export interface QuoteSubmissionSummary {
  id: string;
  rfq_id?: string;
  vendor_id?: string;
  vendor_name?: string | null;
  file_name?: string | null;
  status?: string;
  blocking_issue_count?: number | null;
  original_filename?: string | null;
  file_type?: string | null;
  submitted_at?: string | null;
  confidence?: number | null;
  line_items_count?: number | null;
  warnings_count?: number | null;
  errors_count?: number | null;
  error_code?: string | null;
  error_message?: string | null;
  processing_started_at?: string | null;
  processing_completed_at?: string | null;
  parsed_at?: string | null;
  retry_count?: number | null;
}

function normalizeQuoteSubmission(payload: unknown): QuoteSubmissionSummary {
  const raw = (payload as { data?: Record<string, unknown> })?.data ?? (payload as Record<string, unknown>);
  return {
    id: String(raw?.id ?? ''),
    rfq_id: raw?.rfq_id !== undefined ? String(raw.rfq_id) : undefined,
    vendor_id: raw?.vendor_id !== undefined ? String(raw.vendor_id) : undefined,
    vendor_name: normalizeNullableString(raw?.vendor_name),
    file_name:
      raw?.original_filename !== undefined && raw.original_filename !== null
        ? String(raw.original_filename)
        : raw?.file_path !== undefined && raw.file_path !== null
          ? String(raw.file_path)
          : undefined,
    status: raw?.status !== undefined ? String(raw.status) : undefined,
    blocking_issue_count: normalizeNullableNumber(raw?.blocking_issue_count, 'blocking_issue_count'),
    original_filename: normalizeNullableString(raw?.original_filename),
    file_type: normalizeNullableString(raw?.file_type),
    submitted_at: normalizeNullableString(raw?.submitted_at),
    confidence: normalizeNullableNumber(raw?.confidence, 'confidence'),
    line_items_count: normalizeNullableNumber(raw?.line_items_count, 'line_items_count'),
    warnings_count: normalizeNullableNumber(raw?.warnings_count, 'warnings_count'),
    errors_count: normalizeNullableNumber(raw?.errors_count, 'errors_count'),
    error_code: normalizeNullableString(raw?.error_code),
    error_message:
      normalizeNullableString(raw?.error_message),
    processing_started_at: normalizeNullableString(raw?.processing_started_at),
    processing_completed_at: normalizeNullableString(raw?.processing_completed_at),
    parsed_at: normalizeNullableString(raw?.parsed_at),
    retry_count: normalizeNullableNumber(raw?.retry_count, 'retry_count'),
  };
}

function normalizeNullableString(value: unknown): string | null | undefined {
  if (value === undefined) return undefined;
  if (value === null) return null;
  return String(value);
}

function normalizeNullableNumber(value: unknown, field: string): number | null | undefined {
  if (value === undefined) return undefined;
  if (value === null) return null;
  if (typeof value === 'string' && value.trim() === '') {
    throw new Error(`Invalid quote submission field ${field}: empty string`);
  }
  const n = Number(value);
  if (!Number.isFinite(n)) {
    throw new Error(`Invalid quote submission field ${field}: ${String(value)}`);
  }
  return n;
}

export function useQuoteSubmission(quoteId: string, options?: { enabled?: boolean }) {
  const enabled = (options?.enabled ?? true) && Boolean(quoteId);

  return useQuery({
    queryKey: ['quote-submissions', quoteId],
    queryFn: async (): Promise<QuoteSubmissionSummary> => {
      const data = await fetchLiveOrFail<{ data: QuoteSubmissionSummary }>(`/quote-submissions/${encodeURIComponent(quoteId)}`);

      if (data === undefined) {
        return {
          id: quoteId,
          status: 'ready',
          blocking_issue_count: 0,
          vendor_name: 'Vendor',
        };
      }
      return normalizeQuoteSubmission(data);
    },
    enabled,
  });
}
