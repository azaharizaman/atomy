'use client';

import { useQuery } from '@tanstack/react-query';
import type { AxiosError } from 'axios';

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
  status: string;
  eligibleCandidates: VendorRecommendationCandidate[];
  excludedCandidates: VendorRecommendationExcludedReason[];
  providerExplanation: string | null;
  deterministicReasonSet: string[];
  provenance: Record<string, unknown> | null;
  candidates: VendorRecommendationCandidate[];
  excludedReasons: VendorRecommendationExcludedReason[];
}

interface VendorRecommendationFallbackContext {
  tenantId?: string | null;
  rfqId?: string | null;
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

function optionalText(value: unknown): string | null {
  return toText(value);
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
  if (value === undefined || value === null) {
    return [];
  }

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
      pickField(
        row,
        'provider_explanation',
        'providerExplanation',
        'recommended_reason_summary',
        'recommendedReasonSummary',
      ),
      'provider_explanation',
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
  const status = optionalText(pickField(row, 'status'));

  return {
    vendorId: requireText(pickField(row, 'vendor_id', 'vendorId'), 'vendor_id', context),
    vendorName: requireText(pickField(row, 'vendor_name', 'vendorName'), 'vendor_name', context),
    reason: requireText(pickField(row, 'reason'), 'reason', context),
    status,
  };
}

function normalizeResultList(value: unknown, itemNormalizer: (row: unknown, index: number) => VendorRecommendationCandidate) {
  if (value === undefined || value === null) {
    return [];
  }

  if (!Array.isArray(value)) {
    throw new Error('Invalid vendor recommendation payload: expected an array.');
  }

  return value.map(itemNormalizer);
}

function normalizeExcludedList(value: unknown) {
  if (value === undefined || value === null) {
    return [];
  }

  if (!Array.isArray(value)) {
    throw new Error('Invalid vendor recommendation payload: expected an array.');
  }

  return value.map((row, index) => normalizeExcluded(row, index));
}

function resolveRecommendationSource(payload: unknown): Record<string, unknown> | null {
  if (!isObject(payload)) {
    return null;
  }

  if (isObject(payload.data)) {
    return payload.data;
  }

  return payload;
}

function normalizeStatus(source: Record<string, unknown>): string {
  const status = optionalText(pickField(source, 'status'));
  if (status !== null) {
    return status;
  }

  const error = pickField(source, 'error');
  if (isObject(error)) {
    const errorCode = optionalText(pickField(error, 'code'));
    if (errorCode !== null) {
      return errorCode === 'ai_unavailable' ? 'unavailable' : errorCode;
    }
  }

  return 'available';
}

function normalizeProvenance(value: unknown): Record<string, unknown> | null {
  return isObject(value) ? value : null;
}

export function normalizeVendorRecommendationPayload(
  payload: unknown,
  fallbackContext: VendorRecommendationFallbackContext = {},
): VendorRecommendationResult {
  const envelope = isObject(payload) ? payload : null;
  const source = resolveRecommendationSource(payload);
  if (source === null) {
    throw new Error('Invalid vendor recommendation payload: expected object envelope with data object.');
  }

  const status = normalizeStatus(source);
  const error = pickField(source, 'error');
  const payloadObject = isObject(error) ? error : source;
  const eligibleCandidates = normalizeResultList(
    pickField(payloadObject, 'eligible_candidates', 'eligibleCandidates', 'candidates'),
    normalizeCandidate,
  );
  const excludedCandidates = normalizeExcludedList(
    pickField(payloadObject, 'excluded_candidates', 'excludedCandidates', 'excluded_reasons', 'excludedReasons'),
  );
  const providerExplanation =
    optionalText(pickField(payloadObject, 'provider_explanation', 'providerExplanation')) ??
    optionalText(isObject(error) ? pickField(error, 'message') : undefined) ??
    optionalText(envelope ? pickField(envelope, 'message') : undefined);

  const deterministicReasonSet = stringList(
    pickField(payloadObject, 'deterministic_reason_set', 'deterministicReasonSet', 'deterministic_reasons'),
    'deterministic_reason_set',
    'Invalid vendor recommendation payload',
  );
  const provenance = normalizeProvenance(pickField(payloadObject, 'provenance'));

  const tenantId =
    optionalText(pickField(source, 'tenant_id', 'tenantId')) ?? optionalText(fallbackContext.tenantId) ?? '';
  const rfqId = optionalText(pickField(source, 'rfq_id', 'rfqId')) ?? optionalText(fallbackContext.rfqId) ?? '';

  return {
    tenantId,
    rfqId,
    status,
    eligibleCandidates,
    excludedCandidates,
    providerExplanation,
    deterministicReasonSet,
    provenance,
    candidates: eligibleCandidates,
    excludedReasons: excludedCandidates,
  };
}

function isUnavailableResponse(error: unknown): error is AxiosError {
  if (error === null || error === undefined || typeof error !== 'object') {
    return false;
  }

  const axiosError = error as AxiosError;
  const payload = axiosError.response?.data;
  if (!isObject(payload)) {
    return false;
  }

  const source = isObject(payload.data) ? payload.data : payload;
  const structuredError = pickField(source, 'error');
  if (isObject(structuredError) && optionalText(pickField(structuredError, 'code')) === 'ai_unavailable') {
    return true;
  }

  return optionalText(pickField(source, 'status')) === 'unavailable';
}

async function fetchVendorRecommendations(rfqId: string): Promise<VendorRecommendationResult> {
  const authState = useAuthStore.getState();
  const token = authState.token;
  const tenantId = authState.user?.tenantId ?? null;
  const idempotencyKey =
    typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function'
      ? crypto.randomUUID()
      : `vendor-recommendations-${rfqId}-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;

  try {
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

    return normalizeVendorRecommendationPayload(response.data, { tenantId, rfqId });
  } catch (error) {
    if (isUnavailableResponse(error)) {
      return normalizeVendorRecommendationPayload((error as AxiosError).response?.data, { tenantId, rfqId });
    }

    throw error;
  }
}

export function useVendorRecommendations(rfqId: string) {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

  return useQuery({
    queryKey: ['rfqs', rfqId, 'vendor-recommendations'],
    enabled: !useMocks && Boolean(rfqId),
    queryFn: () => fetchVendorRecommendations(rfqId),
  });
}
