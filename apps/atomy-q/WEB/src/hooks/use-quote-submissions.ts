'use client';

import { useQuery } from '@tanstack/react-query';
import { fetchLiveOrFail } from '@/lib/api-live';
import { isObject, toText } from '@/hooks/normalize-utils';

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
  if (!isObject(payload)) {
    throw new Error('Invalid quote submission response: expected object envelope with data array.');
  }

  if (!Array.isArray(payload.data)) {
    throw new Error('Invalid quote submission response: expected data array.');
  }

  const list = payload.data;
  return list.map((item: unknown, index: number) => {
    if (item === null || typeof item !== 'object' || Array.isArray(item)) {
      throw new Error(`Invalid quote submission row at index ${index}: expected object`);
    }
    const row = item as Record<string, unknown>;
    const id = toText(row.id);
    const rfqId = toText(row.rfq_id);
    const vendorId = toText(row.vendor_id);
    const vendorName = toText(row.vendor_name);
    if (id === null || rfqId === null || vendorId === null || vendorName === null) {
      throw new Error(`Invalid quote submission row at index ${index}: missing id, rfq_id, vendor_id, or vendor_name`);
    }

    const confidence = normalizeConfidence(row.confidence, index);
    const blockingIssueCount = normalizeFiniteNumber(row.blocking_issue_count, 'blocking_issue_count', index);
    const uploadedAt = normalizeRequiredString(row.submitted_at, 'submitted_at', index);
    return {
      id,
      rfq_id: rfqId,
      vendor_id: vendorId,
      vendor_name: vendorName,
      file_name:
        String(row.original_filename ?? row.file_name ?? row.file_path ?? '').trim() || `Quote ${index + 1}`,
      status: String(row.status ?? 'uploaded'),
      confidence,
      uploaded_at: uploadedAt,
      blocking_issue_count: blockingIssueCount,
      original_filename:
        row.original_filename !== undefined && row.original_filename !== null ? String(row.original_filename) : null,
    };
  });
}

function normalizeConfidence(value: unknown, index: number): string {
  if (value === null || value === undefined) {
    throw new Error(`Invalid quote submission row at index ${index}: missing confidence`);
  }
  if (typeof value === 'string') {
    const trimmed = value.trim().toLowerCase();
    if (trimmed === 'high' || trimmed === 'medium' || trimmed === 'low') {
      return trimmed;
    }
    if (trimmed === '') {
      throw new Error(`Invalid quote submission row at index ${index}: empty confidence`);
    }
  }

  const numericConfidence = Number(value);
  if (!Number.isFinite(numericConfidence)) {
    throw new Error(`Invalid quote submission row at index ${index}: confidence`);
  }

  if (numericConfidence >= 90) return 'high';
  if (numericConfidence >= 70) return 'medium';
  return 'low';
}

function normalizeFiniteNumber(value: unknown, field: string, index: number): number {
  if (value === null || value === undefined) {
    throw new Error(`Invalid quote submission row at index ${index}: missing ${field}`);
  }
  if (typeof value === 'string' && value.trim() === '') {
    throw new Error(`Invalid quote submission row at index ${index}: empty ${field}`);
  }
  const numericValue = Number(value);
  if (!Number.isFinite(numericValue)) {
    throw new Error(`Invalid quote submission row at index ${index}: ${field}`);
  }
  return numericValue;
}

function normalizeRequiredString(value: unknown, field: string, index: number): string {
  if (value === null || value === undefined) {
    throw new Error(`Invalid quote submission row at index ${index}: missing ${field}`);
  }
  const s = String(value).trim();
  if (s === '') {
    throw new Error(`Invalid quote submission row at index ${index}: empty ${field}`);
  }
  return s;
}

export function useQuoteSubmissions(rfqId: string) {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

  return useQuery({
    queryKey: ['quote-submissions', 'list', rfqId],
    queryFn: async (): Promise<QuoteSubmissionRow[]> => {
      if (useMocks) {
        const { getSeedQuotesByRfqId } = await import('@/data/seed');
        const seedRows = getSeedQuotesByRfqId(rfqId).map((row) => ({
          id: row.id,
          rfq_id: row.rfqId,
          vendor_id: row.vendorId,
          vendor_name: row.vendorName,
          file_name: row.fileName,
          status: row.status,
          confidence: row.confidence,
          submitted_at: row.uploadedAt,
          blocking_issue_count: 0,
          original_filename: row.fileName,
        }));
        return normalizeQuoteSubmissionRows({ data: seedRows });
      }

      const data = await fetchLiveOrFail<{ data: QuoteSubmissionRow[] }>('/quote-submissions', {
        params: { rfq_id: rfqId },
      });

      if (data === undefined) {
        throw new Error(`Quote submissions unavailable for RFQ "${rfqId}".`);
      }

      return normalizeQuoteSubmissionRows(data);
    },
    enabled: Boolean(rfqId),
  });
}
