'use client';

import { useQuery } from '@tanstack/react-query';
import { fetchLiveOrFail } from '@/lib/api-live';
import { isObject, toText } from '@/hooks/normalize-utils';

export interface NormalizationSourceLineRow {
  id: string;
  quote_submission_id: string;
  vendor_id: string;
  vendor_name: string;
  source_description: string;
  source_quantity: string | null;
  source_uom: string | null;
  source_unit_price: string | null;
  rfq_line_item_id: string | null;
  rfq_line_description: string | null;
  rfq_line_quantity: string | null;
  rfq_line_uom: string | null;
  rfq_line_unit_price: string | null;
  sort_order: number | null;
  confidence: string;
  conflict_count: number | null;
  blocking_issue_count: number | null;
  has_blocking_issue: boolean;
  quote_submission_status: string | null;
}

function normalizeSourceLines(payload: unknown): NormalizationSourceLineRow[] {
  if (!isObject(payload)) {
    throw new Error('Invalid normalization source line response: expected object envelope with data array.');
  }

  if (!Array.isArray(payload.data)) {
    throw new Error('Invalid normalization source line response: expected data array.');
  }

  const list = payload.data;

  return list.map((item: unknown, index: number) => {
    if (item === null || typeof item !== 'object' || Array.isArray(item)) {
      throw new Error(`Invalid normalization source line at index ${index}: expected object`);
    }

    const row = item as Record<string, unknown>;
    const id = toText(row.id);
    const quoteSubmissionId = toText(row.quote_submission_id);
    const vendorId = toText(row.vendor_id);
    const vendorName = toText(row.vendor_name);
    const sourceDescription = toText(row.source_description);
    if (id === null || quoteSubmissionId === null || vendorId === null || vendorName === null || sourceDescription === null) {
      throw new Error(`Invalid normalization source line at index ${index}: missing required fields`);
    }

    return {
      id,
      quote_submission_id: quoteSubmissionId,
      vendor_id: vendorId,
      vendor_name: vendorName,
      source_description: sourceDescription,
      source_quantity:
        row.source_quantity !== undefined && row.source_quantity !== null ? String(row.source_quantity) : null,
      source_uom: row.source_uom !== undefined && row.source_uom !== null ? String(row.source_uom) : null,
      source_unit_price:
        row.source_unit_price !== undefined && row.source_unit_price !== null ? String(row.source_unit_price) : null,
      rfq_line_item_id:
        row.rfq_line_item_id !== undefined && row.rfq_line_item_id !== null ? String(row.rfq_line_item_id) : null,
      rfq_line_description:
        row.rfq_line_description !== undefined && row.rfq_line_description !== null
          ? String(row.rfq_line_description)
          : null,
      rfq_line_quantity:
        row.rfq_line_quantity !== undefined && row.rfq_line_quantity !== null ? String(row.rfq_line_quantity) : null,
      rfq_line_uom: row.rfq_line_uom !== undefined && row.rfq_line_uom !== null ? String(row.rfq_line_uom) : null,
      rfq_line_unit_price:
        row.rfq_line_unit_price !== undefined && row.rfq_line_unit_price !== null
          ? String(row.rfq_line_unit_price)
          : null,
      sort_order: parseOptionalNumber(row.sort_order),
      confidence: String(row.confidence ?? 'low'),
      conflict_count: parseOptionalNumber(row.conflict_count),
      blocking_issue_count: parseOptionalNumber(row.blocking_issue_count),
      has_blocking_issue: Boolean(row.has_blocking_issue),
      quote_submission_status:
        row.quote_submission_status !== undefined && row.quote_submission_status !== null
          ? String(row.quote_submission_status)
          : null,
    };
  });
}

function parseOptionalNumber(value: unknown): number | null {
  if (value === null || value === undefined) return null;
  if (typeof value === 'string' && value.trim() === '') return null;
  const n = Number(value);
  return Number.isFinite(n) ? n : null;
}

export function useNormalizationSourceLines(rfqId: string, options?: { enabled?: boolean }) {
  const enabled = (options?.enabled ?? true) && Boolean(rfqId);
  const isMock = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

  return useQuery({
    queryKey: ['normalization-source-lines', rfqId],
    queryFn: async (): Promise<NormalizationSourceLineRow[]> => {
      const data = await fetchLiveOrFail<{ data: NormalizationSourceLineRow[] }>(
        '/normalization/' + encodeURIComponent(rfqId) + '/source-lines',
      );

      if (data === undefined) {
        if (isMock) {
          return [];
        }
        throw new Error(`Normalization source lines unavailable for RFQ "${rfqId}".`);
      }

      return normalizeSourceLines(data);
    },
    enabled,
  });
}
