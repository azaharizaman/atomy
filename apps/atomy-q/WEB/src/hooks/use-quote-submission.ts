'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';

export interface QuoteSubmissionSummary {
  id: string;
  rfq_id?: string;
  vendor_id?: string;
  vendor_name?: string;
  file_name?: string;
  status?: string;
  blocking_issue_count?: number;
  original_filename?: string;
  file_type?: string;
  submitted_at?: string;
  confidence?: number;
  line_items_count?: number;
  warnings_count?: number;
  errors_count?: number;
  error_code?: string | null;
  error_message?: string | null;
  processing_started_at?: string | null;
  processing_completed_at?: string | null;
  parsed_at?: string | null;
  retry_count?: number;
}

function normalizeQuoteSubmission(payload: unknown): QuoteSubmissionSummary {
  const raw = (payload as { data?: Record<string, unknown> })?.data ?? (payload as Record<string, unknown>);
  return {
    id: String(raw?.id ?? ''),
    rfq_id: raw?.rfq_id !== undefined ? String(raw.rfq_id) : undefined,
    vendor_id: raw?.vendor_id !== undefined ? String(raw.vendor_id) : undefined,
    vendor_name: raw?.vendor_name !== undefined ? String(raw.vendor_name) : undefined,
    file_name:
      raw?.original_filename !== undefined && raw.original_filename !== null
        ? String(raw.original_filename)
        : raw?.file_path !== undefined && raw.file_path !== null
          ? String(raw.file_path)
          : undefined,
    status: raw?.status !== undefined ? String(raw.status) : undefined,
    blocking_issue_count:
      raw?.blocking_issue_count !== undefined ? Number(raw.blocking_issue_count) : undefined,
    original_filename: raw?.original_filename !== undefined ? String(raw.original_filename) : undefined,
    file_type: raw?.file_type !== undefined ? String(raw.file_type) : undefined,
    submitted_at: raw?.submitted_at !== undefined ? String(raw.submitted_at) : undefined,
    confidence: raw?.confidence !== undefined ? Number(raw.confidence) : undefined,
    line_items_count: raw?.line_items_count !== undefined ? Number(raw.line_items_count) : undefined,
    warnings_count: raw?.warnings_count !== undefined ? Number(raw.warnings_count) : undefined,
    errors_count: raw?.errors_count !== undefined ? Number(raw.errors_count) : undefined,
    error_code: raw?.error_code !== undefined ? (raw.error_code === null ? null : String(raw.error_code)) : undefined,
    error_message:
      raw?.error_message !== undefined ? (raw.error_message === null ? null : String(raw.error_message)) : undefined,
    processing_started_at:
      raw?.processing_started_at !== undefined
        ? (raw.processing_started_at === null ? null : String(raw.processing_started_at))
        : undefined,
    processing_completed_at:
      raw?.processing_completed_at !== undefined
        ? (raw.processing_completed_at === null ? null : String(raw.processing_completed_at))
        : undefined,
    parsed_at: raw?.parsed_at !== undefined ? (raw.parsed_at === null ? null : String(raw.parsed_at)) : undefined,
    retry_count: raw?.retry_count !== undefined ? Number(raw.retry_count) : undefined,
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
