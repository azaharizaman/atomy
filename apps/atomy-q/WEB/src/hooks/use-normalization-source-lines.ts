'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { fetchLiveOrFail } from '@/lib/api-live';
import { isObject, toText } from '@/hooks/normalize-utils';

export interface ManualSourceLineInput {
  source_description: string;
  source_quantity?: string | null;
  source_uom?: string | null;
  source_unit_price?: string | null;
  rfq_line_item_id?: string | null;
  note?: string | null;
  sort_order?: number | null;
  reason?: string | null;
}

export interface NormalizationValueSnapshot {
  source_description?: string | null;
  rfq_line_item_id: string | null;
  quantity: string | null;
  uom: string | null;
  unit_price: string | null;
}

export interface NormalizationOverrideData extends NormalizationValueSnapshot {
  source_description: string;
}

export interface LatestNormalizationOverride {
  reason_code: string | null;
  note: string | null;
  actor_name: string | null;
  actor_user_id: string | null;
  overridden_at: string | null;
  provider_confidence: string | null;
}

export interface CreateManualSourceLineInput extends ManualSourceLineInput {
  quoteSubmissionId: string;
}

export interface UpdateManualSourceLineInput extends ManualSourceLineInput {
  id: string;
  quoteSubmissionId: string;
}

export interface DeleteManualSourceLineInput {
  id: string;
  quoteSubmissionId: string;
}

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
  extraction_origin?: string | null;
  origin?: string | null;
  provider_name?: string | null;
  ai_confidence?: string | null;
  provider_suggested?: NormalizationValueSnapshot | null;
  effective_values?: NormalizationValueSnapshot | null;
  is_buyer_overridden: boolean;
  latest_override?: LatestNormalizationOverride | null;
}

function normalizeValueSnapshot(value: unknown): NormalizationValueSnapshot | null {
  if (!isObject(value)) {
    return null;
  }

  return {
    source_description: toText(value.source_description),
    rfq_line_item_id: toText(value.rfq_line_item_id),
    quantity: toText(value.quantity),
    uom: toText(value.uom),
    unit_price: toText(value.unit_price),
  };
}

function normalizeLatestOverride(value: unknown): LatestNormalizationOverride | null {
  if (!isObject(value)) {
    return null;
  }

  return {
    reason_code: toText(value.reason_code),
    note: toText(value.note),
    actor_name: toText(value.actor_name),
    actor_user_id: toText(value.actor_user_id),
    overridden_at: toText(value.overridden_at),
    provider_confidence: toText(value.provider_confidence),
  };
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
      extraction_origin:
        row.extraction_origin !== undefined && row.extraction_origin !== null ? String(row.extraction_origin) : null,
      origin: row.origin !== undefined && row.origin !== null ? String(row.origin) : null,
      provider_name: row.provider_name !== undefined && row.provider_name !== null ? String(row.provider_name) : null,
      ai_confidence: row.ai_confidence !== undefined && row.ai_confidence !== null ? String(row.ai_confidence) : null,
      provider_suggested: normalizeValueSnapshot(row.provider_suggested),
      effective_values: normalizeValueSnapshot(row.effective_values),
      is_buyer_overridden: Boolean(row.is_buyer_overridden),
      latest_override: normalizeLatestOverride(row.latest_override),
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

function manualPayload(input: ManualSourceLineInput) {
  return {
    source_description: input.source_description,
    source_quantity: input.source_quantity ?? null,
    source_uom: input.source_uom ?? null,
    source_unit_price: input.source_unit_price ?? null,
    rfq_line_item_id: input.rfq_line_item_id ?? null,
    note: input.note ?? null,
    sort_order: input.sort_order ?? null,
    origin: 'manual',
    reason: input.reason ?? null,
  };
}

export function useManualNormalizationSourceLineMutations(rfqId: string) {
  const qc = useQueryClient();

  function invalidate(): void {
    qc.invalidateQueries({ queryKey: ['normalization-source-lines', rfqId] });
    qc.invalidateQueries({ queryKey: ['normalization-conflicts', rfqId] });
    qc.invalidateQueries({ queryKey: ['quote-submissions', 'list', rfqId] });
    qc.invalidateQueries({ queryKey: ['rfqs', rfqId, 'overview'] });
  }

  const createSourceLine = useMutation({
    mutationFn: async (input: CreateManualSourceLineInput) => {
      const { quoteSubmissionId, ...payloadInput } = input;
      const { data } = await api.post(
        `/quote-submissions/${encodeURIComponent(quoteSubmissionId)}/source-lines`,
        manualPayload(payloadInput),
      );
      return data;
    },
    onSuccess: invalidate,
  });

  const overrideSourceLine = useMutation({
    mutationFn: async (input: { id: string; override_data: NormalizationOverrideData; reason_code: string; note: string | null }) => {
      const { id, override_data, reason_code, note } = input;
      const { data } = await api.put(
        `/normalization/source-lines/${encodeURIComponent(id)}/override`,
        { override_data, reason_code, note },
      );
      return data;
    },
    onSuccess: invalidate,
  });

  const deleteSourceLine = useMutation({
    mutationFn: async (input: DeleteManualSourceLineInput) => {
      const { data } = await api.delete(
        `/quote-submissions/${encodeURIComponent(input.quoteSubmissionId)}/source-lines/${encodeURIComponent(input.id)}`,
      );
      return data;
    },
    onSuccess: invalidate,
  });

  return { createSourceLine, overrideSourceLine, deleteSourceLine };
}
