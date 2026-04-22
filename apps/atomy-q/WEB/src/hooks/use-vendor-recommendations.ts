'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { isObject, toText } from '@/hooks/normalize-utils';
import { useAuthStore } from '@/store/use-auth-store';

export interface VendorRecommendationCandidate {
  vendorId: string;
  vendorName: string;
  fitScore: number;
  confidenceBand: string;
  recommendedReasonSummary: string;
  deterministicReasons: string[];
  llmInsights: string[];
  warningFlags: string[];
  warnings: string[];
}

export interface VendorRecommendationExcludedReason {
  vendorId: string;
  vendorName: string;
  reason: string;
  status: string | null;
}

export interface VendorRecommendationResult {
  tenantId: string;
  rfqId: string;
  candidates: VendorRecommendationCandidate[];
  excludedReasons: VendorRecommendationExcludedReason[];
}

function pickField(item: Record<string, unknown>, ...keys: string[]): unknown {
  for (const key of keys) {
    if (key in item) {
      return item[key];
    }
  }

  return undefined;
}

function requireText(value: unknown, field: string, context: string): string {
  const text = toText(value);
  if (text === null) {
    throw new Error(`${context}: missing ${field}`);
  }

  return text;
}

function requireNumber(value: unknown, field: string, context: string): number {
  if (typeof value === 'number' && Number.isFinite(value)) {
    return value;
  }

  const text = toText(value);
  if (text !== null) {
    const parsed = Number(text);
    if (Number.isFinite(parsed)) {
      return parsed;
    }
  }

  throw new Error(`${context}: ${field} must be finite`);
}

function stringList(value: unknown, field: string, context: string): string[] {
  if (!Array.isArray(value)) {
    throw new Error(`${context}: ${field} must be an array`);
  }

  return value.map((item, index) => requireText(item, `${field}.${index}`, context));
}

function normalizeCandidate(row: unknown, index: number): VendorRecommendationCandidate {
  if (!isObject(row)) {
    throw new Error(`Invalid vendor recommendation candidate at index ${index}: expected object`);
  }

  const context = `Invalid vendor recommendation candidate at index ${index}`;

  return {
    vendorId: requireText(pickField(row, 'vendor_id', 'vendorId'), 'vendor_id', context),
    vendorName: requireText(pickField(row, 'vendor_name', 'vendorName'), 'vendor_name', context),
    fitScore: requireNumber(pickField(row, 'fit_score', 'fitScore'), 'fit_score', context),
    confidenceBand: requireText(pickField(row, 'confidence_band', 'confidenceBand'), 'confidence_band', context),
    recommendedReasonSummary: requireText(
      pickField(row, 'recommended_reason_summary', 'recommendedReasonSummary'),
      'recommended_reason_summary',
      context,
    ),
    deterministicReasons: stringList(
      pickField(row, 'deterministic_reasons', 'deterministicReasons'),
      'deterministic_reasons',
      context,
    ),
    llmInsights: stringList(pickField(row, 'llm_insights', 'llmInsights'), 'llm_insights', context),
    warningFlags: stringList(pickField(row, 'warning_flags', 'warningFlags'), 'warning_flags', context),
    warnings: stringList(pickField(row, 'warnings'), 'warnings', context),
  };
}

function normalizeExcluded(row: unknown, index: number): VendorRecommendationExcludedReason {
  if (!isObject(row)) {
    throw new Error(`Invalid vendor recommendation exclusion at index ${index}: expected object`);
  }

  const context = `Invalid vendor recommendation exclusion at index ${index}`;
  const status = toText(pickField(row, 'status'));

  return {
    vendorId: requireText(pickField(row, 'vendor_id', 'vendorId'), 'vendor_id', context),
    vendorName: requireText(pickField(row, 'vendor_name', 'vendorName'), 'vendor_name', context),
    reason: requireText(pickField(row, 'reason'), 'reason', context),
    status,
  };
}

export function normalizeVendorRecommendationPayload(payload: unknown): VendorRecommendationResult {
  const envelope = isObject(payload) ? payload : null;
  const data = envelope !== null && isObject(envelope.data) ? envelope.data : null;
  if (data === null) {
    throw new Error('Invalid vendor recommendation payload: expected object envelope with data object.');
  }

  const candidates = pickField(data, 'candidates');
  const excludedReasons = pickField(data, 'excluded_reasons', 'excludedReasons');

  if (!Array.isArray(candidates) || !Array.isArray(excludedReasons)) {
    throw new Error('Invalid vendor recommendation payload: expected candidates and excluded_reasons arrays.');
  }

  return {
    tenantId: requireText(pickField(data, 'tenant_id', 'tenantId'), 'tenant_id', 'Invalid vendor recommendation payload'),
    rfqId: requireText(pickField(data, 'rfq_id', 'rfqId'), 'rfq_id', 'Invalid vendor recommendation payload'),
    candidates: candidates.map((candidate, index) => normalizeCandidate(candidate, index)),
    excludedReasons: excludedReasons.map((reason, index) => normalizeExcluded(reason, index)),
  };
}

async function fetchVendorRecommendations(rfqId: string): Promise<VendorRecommendationResult> {
  const token = useAuthStore.getState().token;
  const idempotencyKey =
    typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function'
      ? crypto.randomUUID()
      : `vendor-recommendations-${rfqId}-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
  const response = await api.post(
    `/rfqs/${encodeURIComponent(rfqId)}/vendor-recommendations`,
    {},
    {
      headers: {
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        'Idempotency-Key': idempotencyKey,
      },
    },
  );

  return normalizeVendorRecommendationPayload(response.data);
}

export function useVendorRecommendations(rfqId: string) {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

  return useQuery({
    queryKey: ['rfqs', rfqId, 'vendor-recommendations'],
    enabled: !useMocks && Boolean(rfqId),
    queryFn: () => fetchVendorRecommendations(rfqId),
  });
}
