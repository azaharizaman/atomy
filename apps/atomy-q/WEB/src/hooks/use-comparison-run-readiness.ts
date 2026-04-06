'use client';

import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { getSeedComparisonRunsByRfqId } from '@/data/seed';

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

function isObject(value: unknown): value is Record<string, unknown> {
  return value !== null && typeof value === 'object' && !Array.isArray(value);
}

function toText(value: unknown): string | null {
  if (value === null || value === undefined) {
    return null;
  }

  const text = String(value).trim();
  return text === '' ? null : text;
}

function unwrapResponse(payload: unknown): unknown {
  if (!isObject(payload)) {
    return payload;
  }

  if (payload.data !== undefined) {
    return payload.data;
  }

  return payload;
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
    isReady: Boolean(readiness.is_ready ?? readiness.isReady ?? false),
    isPreviewOnly: Boolean(readiness.is_preview_only ?? readiness.isPreviewOnly ?? false),
    blockers: blockersRaw.map((entry, index) => normalizeEntry(entry, index, 'blocker')),
    warnings: warningsRaw.map((entry, index) => normalizeEntry(entry, index, 'warning')),
  };
}

function buildMockReadiness(runId: string, rfqId?: string): ComparisonRunReadiness {
  const run = rfqId ? getSeedComparisonRunsByRfqId(rfqId).find((item) => item.id === runId || item.runId === runId) : null;
  return {
    id: run?.id ?? runId,
    isReady: true,
    isPreviewOnly: run?.type !== 'final',
    blockers: [],
    warnings: [],
  };
}

export function useComparisonRunReadiness(runId: string, options?: { rfqId?: string }) {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

  return useQuery({
    queryKey: ['comparison-run-readiness', options?.rfqId ?? runId, runId],
    queryFn: async (): Promise<ComparisonRunReadiness> => {
      if (useMocks) {
        return buildMockReadiness(runId, options?.rfqId);
      }

      const { data } = await api.get(`/comparison-runs/${encodeURIComponent(runId)}/readiness`);
      return normalizeComparisonRunReadiness(data);
    },
    enabled: Boolean(runId),
  });
}

export { normalizeComparisonRunReadiness };
