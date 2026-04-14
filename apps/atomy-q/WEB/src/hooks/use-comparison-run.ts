'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { fetchLiveOrFail } from '@/lib/api-live';
import { getSeedComparisonRunsByRfqId } from '@/data/seed';
import { isObject, toText, unwrapResponse } from '@/hooks/normalize-utils';

export interface ComparisonRunSnapshotVendor {
  vendorId: string;
  vendorName: string;
  quoteSubmissionId: string | null;
}

export interface ComparisonRunSnapshotNormalizedLine {
  rfqLineItemId: string;
  sourceDescription: string;
  sourceLineId: string | null;
  quoteSubmissionId: string | null;
  vendorId: string | null;
  sourceUnitPrice: string | null;
  sourceUom: string | null;
  sourceQuantity: string | null;
}

export interface ComparisonRunSnapshot {
  rfqVersion: number;
  normalizedLines: ComparisonRunSnapshotNormalizedLine[];
  resolutions: Array<Record<string, unknown>>;
  currencyMeta: Record<string, string>;
  vendors: ComparisonRunSnapshotVendor[];
}

export interface ComparisonRunDetail {
  id: string;
  rfqId: string;
  name: string;
  status: string;
  isPreview: boolean;
  snapshot: ComparisonRunSnapshot | null;
  createdAt: string | null;
}

function toRequiredNumber(value: unknown, fieldName: string): number {
  if (value === null || value === undefined || value === '') {
    throw new Error(`Comparison run snapshot is missing ${fieldName}.`);
  }

  const numberValue = Number(value);
  if (!Number.isFinite(numberValue)) {
    throw new Error(`Comparison run snapshot field ${fieldName} must be numeric.`);
  }

  return numberValue;
}

function normalizeSnapshot(payload: unknown): ComparisonRunSnapshot | null {
  if (payload === null || payload === undefined) {
    return null;
  }

  if (!isObject(payload)) {
    throw new Error('Comparison run snapshot must be an object.');
  }

  const rawNormalizedLines = Array.isArray(payload.normalized_lines)
    ? payload.normalized_lines
    : Array.isArray(payload.normalizedLines)
      ? payload.normalizedLines
      : [];

  const normalizedLines = rawNormalizedLines.map((item: unknown, index: number): ComparisonRunSnapshotNormalizedLine => {
    if (!isObject(item)) {
      throw new Error(`Comparison run snapshot line at index ${index} must be an object.`);
    }

    const rfqLineItemId = toText(item.rfq_line_item_id ?? item.rfqLineItemId);
    if (rfqLineItemId === null) {
      throw new Error(`Comparison run snapshot line at index ${index} is missing rfq_line_item_id.`);
    }

    return {
      rfqLineItemId,
      sourceDescription: toText(item.source_description ?? item.sourceDescription) ?? '',
      sourceLineId: toText(item.source_line_id ?? item.sourceLineId),
      quoteSubmissionId: toText(item.quote_submission_id ?? item.quoteSubmissionId),
      vendorId: toText(item.vendor_id ?? item.vendorId),
      sourceUnitPrice: toText(item.source_unit_price ?? item.sourceUnitPrice),
      sourceUom: toText(item.source_uom ?? item.sourceUom),
      sourceQuantity: toText(item.source_quantity ?? item.sourceQuantity),
    };
  });

  const rawResolutions = Array.isArray(payload.resolutions) ? payload.resolutions : Array.isArray(payload.resolution) ? payload.resolution : [];
  const resolutions = rawResolutions
    .filter(isObject)
    .map((item) => ({ ...item }));

  const rawCurrencyMeta = isObject(payload.currency_meta)
    ? payload.currency_meta
    : isObject(payload.currencyMeta)
      ? payload.currencyMeta
      : {};
  const currencyMeta: Record<string, string> = {};
  Object.entries(rawCurrencyMeta).forEach(([key, value]) => {
    const currency = toText(value);
    if (currency !== null) {
      currencyMeta[key] = currency;
    }
  });

  const rawVendors = Array.isArray(payload.vendors)
    ? payload.vendors
    : Array.isArray(payload.vendorSummaries)
      ? payload.vendorSummaries
      : Array.isArray(payload.vendor_summaries)
        ? payload.vendor_summaries
        : [];
  const vendors = rawVendors.map((item: unknown, index: number): ComparisonRunSnapshotVendor => {
    if (!isObject(item)) {
      throw new Error(`Comparison run snapshot vendor at index ${index} must be an object.`);
    }

    const vendorId = toText(item.vendor_id ?? item.vendorId);
    const vendorName = toText(item.vendor_name ?? item.vendorName);
    if (vendorId === null || vendorName === null) {
      throw new Error(`Comparison run snapshot vendor at index ${index} is missing vendor_id or vendor_name.`);
    }

    return {
      vendorId,
      vendorName,
      quoteSubmissionId: toText(item.quote_submission_id ?? item.quoteSubmissionId),
    };
  });

  const rfqVersion = toRequiredNumber(payload.rfq_version ?? payload.rfqVersion, 'rfq_version');

  return {
    rfqVersion,
    normalizedLines,
    resolutions,
    currencyMeta,
    vendors,
  };
}

function normalizeComparisonRun(payload: unknown): ComparisonRunDetail {
  const raw = unwrapResponse(payload);
  if (!isObject(raw)) {
    throw new Error('Comparison run payload must be an object.');
  }

  const id = toText(raw.id ?? raw.run_id ?? raw.runId);
  if (id === null) {
    throw new Error('Comparison run payload is missing id.');
  }

  const rfqId = toText(raw.rfq_id ?? raw.rfqId);
  if (rfqId === null) {
    throw new Error(`Comparison run "${id}" is missing rfq_id.`);
  }

  const responsePayload = isObject(raw.responsePayload)
    ? raw.responsePayload
    : isObject(raw.response_payload)
      ? raw.response_payload
      : undefined;
  const snapshotPayload = raw.snapshot ?? responsePayload?.snapshot;
  const isPreviewValue = raw.is_preview ?? raw.isPreview;
  const isPreview = typeof isPreviewValue === 'boolean'
    ? isPreviewValue
    : typeof isPreviewValue === 'string'
      ? ['true', '1'].includes(isPreviewValue.trim().toLowerCase())
      : false;

  return {
    id,
    rfqId,
    name: toText(raw.name) ?? 'Comparison Run',
    status: toText(raw.status) ?? 'draft',
    isPreview,
    snapshot: normalizeSnapshot(snapshotPayload),
    createdAt: toText(raw.created_at ?? raw.createdAt),
  };
}

function buildMockComparisonRun(runId: string, rfqId?: string): ComparisonRunDetail {
  const run = rfqId ? getSeedComparisonRunsByRfqId(rfqId).find((item) => item.id === runId || item.runId === runId) : null;
  if (!run) {
    return {
      id: runId,
      rfqId: rfqId ?? '',
      name: 'Comparison Run',
      status: 'draft',
      isPreview: true,
      snapshot: null,
      createdAt: null,
    };
  }

  return {
    id: run.id,
    rfqId: run.rfqId,
    name: run.type === 'final' ? 'Final comparison' : 'Preview comparison',
    status: run.status,
    isPreview: run.type !== 'final',
    snapshot: {
      rfqVersion: 1,
      normalizedLines: [],
      resolutions: [],
      currencyMeta: {},
      vendors: [],
    },
    createdAt: null,
  };
}

export function useComparisonRun(runId: string, options?: { rfqId?: string }) {
  return useQuery({
    queryKey: ['comparison-run', options?.rfqId ?? runId, runId],
    queryFn: async (): Promise<ComparisonRunDetail> => {
      const data = await fetchLiveOrFail<{ data: ComparisonRunDetail }>(`/comparison-runs/${encodeURIComponent(runId)}`);

      if (data === undefined) {
        return buildMockComparisonRun(runId, options?.rfqId);
      }

      const data = await fetchLiveOrFail(`/comparison-runs/${encodeURIComponent(runId)}`);
      if (data === undefined) {
        return buildMockComparisonRun(runId, options?.rfqId);
      }

      const normalizedRun = normalizeComparisonRun(data);

      if (options?.rfqId !== undefined && normalizedRun.rfqId !== options.rfqId) {
        throw new Error(`Comparison run "${normalizedRun.id}" does not belong to RFQ "${options.rfqId}".`);
      }

      return normalizedRun;
    },
    enabled: Boolean(runId),
  });
}

export { normalizeComparisonRun, normalizeSnapshot };
