'use client';

import { useQuery } from '@tanstack/react-query';

import { api } from '@/lib/api';
import { isObject, toText, unwrapResponse } from '@/hooks/normalize-utils';
import { useAiStatus } from '@/hooks/use-ai-status';

export interface AiNarrativeSummary {
  featureKey: string;
  available: boolean;
  headline: string | null;
  summary: string | null;
  bullets: string[];
  provenance: Record<string, unknown> | null;
  raw: Record<string, unknown> | null;
}

export interface UseAiNarrativeSummaryResult {
  summary: AiNarrativeSummary | null;
  isLoading: boolean;
  isError: boolean;
  error: Error | null;
  isHidden: boolean;
  shouldShowUnavailableMessage: boolean;
  messageKey: string | null;
}

function normalizeStringList(value: unknown): string[] {
  if (!Array.isArray(value)) {
    return [];
  }

  return value
    .map((item) => toText(item))
    .filter((item): item is string => item !== null);
}

function normalizeProvenance(value: unknown): Record<string, unknown> | null {
  return isObject(value) ? value : null;
}

function pickNarrativeContainer(value: Record<string, unknown>): Record<string, unknown> {
  const candidate =
    value.ai_narrative
    ?? value.aiNarrative
    ?? value.ai_summary
    ?? value.aiSummary
    ?? value.ai_insights
    ?? value.aiInsights
    ?? value;
  return isObject(candidate) ? candidate : value;
}

export function normalizeAiNarrativePayload(payload: unknown): AiNarrativeSummary {
  const raw = unwrapResponse(payload);
  if (!isObject(raw)) {
    throw new Error('AI narrative payload must be an object.');
  }

  const narrative = pickNarrativeContainer(raw);
  const featureKey = toText(narrative.feature_key ?? narrative.featureKey);
  if (featureKey === null) {
    throw new Error('AI narrative payload is missing feature_key.');
  }

  const provenance = normalizeProvenance(narrative.provenance ?? raw.provenance);
  const headline = toText(narrative.headline ?? narrative.title ?? narrative.message);
  const summary = toText(narrative.summary ?? narrative.description);
  const bullets =
    normalizeStringList(
      narrative.bullets
      ?? narrative.key_points
      ?? narrative.keyPoints
      ?? narrative.highlights
      ?? narrative.rationale,
    );

  return {
    featureKey,
    available: narrative.available === true,
    headline,
    summary,
    bullets,
    provenance,
    raw: narrative,
  };
}

export function useAiNarrativeSummary(
  path: string,
  featureKey: string,
  options?: {
    enabled?: boolean;
    queryKey?: readonly unknown[];
  },
): UseAiNarrativeSummaryResult {
  const aiStatus = useAiStatus();
  const isFeatureAvailable = aiStatus.isFeatureAvailable(featureKey);
  const shouldHideAiControls = aiStatus.shouldHideAiControls(featureKey);
  const shouldShowUnavailableMessage = aiStatus.shouldShowUnavailableMessage(featureKey);
  const messageKey = aiStatus.messageKeyForFeature(featureKey);
  const enabled = Boolean(path) && options?.enabled !== false && isFeatureAvailable;

  const query = useQuery({
    queryKey: options?.queryKey ?? ['ai-narrative-summary', featureKey, path],
    enabled,
    queryFn: async (): Promise<AiNarrativeSummary> => {
      const response = await api.get(path);

      if (response.data == null) {
        throw new Error(`AI narrative "${featureKey}" is unavailable from the live API.`);
      }

      return normalizeAiNarrativePayload(response.data);
    },
    retry: false,
    staleTime: 60 * 1000,
    refetchOnReconnect: false,
    refetchOnWindowFocus: false,
  });

  return {
    summary: query.data ?? null,
    isLoading: query.isLoading,
    isError: query.isError,
    error: query.error ?? null,
    isHidden: shouldHideAiControls,
    shouldShowUnavailableMessage,
    messageKey,
  };
}
