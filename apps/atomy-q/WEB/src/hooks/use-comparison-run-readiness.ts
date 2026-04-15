'use client';

import { useQuery } from '@tanstack/react-query';
import { fetchLiveOrFail } from '@/lib/api-live';
import { isObject, toText, unwrapResponse } from '@/hooks/normalize-utils';

export interface ComparisonRunReadinessEntry {
  code: string;
  message: string;
}

export interface ComparisonRunReadiness {
  id: string;
  isReady: boolean;
  isPreviewOnly: boolean;
  blockers: ComparisonRunReadinessEntry[];
  warnings: ComparisonRunReadinessEntry[];
}

function parseExplicitBoolean(value: unknown): boolean {
  if (typeof value === 'boolean') {
    return value;
  }

  if (typeof value === 'number') {
    return value === 1;
  }

  if (typeof value === 'string') {
    const normalized = value.trim().toLowerCase();
    return normalized === 'true' || normalized === '1';
  }

  return false;
}

function normalizeEntry(entry: unknown, index: number, kind: 'blocker' | 'warning'): ComparisonRunReadinessEntry {
  if (!isObject(entry)) {
    throw new Error(`Comparison readiness ${kind} at index ${index} must be an object.`);
  }

  const code = toText(entry.code);
  const message = toText(entry.message);
  if (code === null || message === null) {
    throw new Error(`Comparison readiness ${kind} at index ${index} is missing code or message.`);
  }

  return { code, message };
}

function normalizeComparisonRunReadiness(payload: unknown): ComparisonRunReadiness {
  const raw = unwrapResponse(payload);
  if (!isObject(raw)) {
    throw new Error('Comparison readiness payload must be an object.');
  }

  const id = toText(raw.id ?? raw.run_id ?? raw.runId);
  if (id === null) {
    throw new Error('Comparison readiness payload is missing id.');
  }

  const readiness = isObject(raw.readiness) ? raw.readiness : raw;
  const blockersRaw = Array.isArray(readiness.blockers) ? readiness.blockers : [];
  const warningsRaw = Array.isArray(readiness.warnings) ? readiness.warnings : [];

  return {
    id,
    isReady: parseExplicitBoolean(readiness.is_ready ?? readiness.isReady),
    isPreviewOnly: parseExplicitBoolean(readiness.is_preview_only ?? readiness.isPreviewOnly),
    blockers: blockersRaw.map((entry, index) => normalizeEntry(entry, index, 'blocker')),
    warnings: warningsRaw.map((entry, index) => normalizeEntry(entry, index, 'warning')),
  };
}

export function useComparisonRunReadiness(runId: string, options?: { rfqId?: string }) {
  const isMock = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

  return useQuery({
    queryKey: ['comparison-run-readiness', options?.rfqId ?? runId, runId],
    queryFn: async (): Promise<ComparisonRunReadiness> => {
      const data = await fetchLiveOrFail<{ data: ComparisonRunReadiness }>(`/comparison-runs/${encodeURIComponent(runId)}/readiness`);

      if (data === undefined) {
        throw new Error(`Comparison readiness unavailable for run "${runId}".`);
      }

      return normalizeComparisonRunReadiness(data);
    },
    enabled: Boolean(runId) && !isMock,
  });
}

export { normalizeComparisonRunReadiness };
