'use client';

import { useQuery } from '@tanstack/react-query';
import { fetchLiveOrFail } from '@/lib/api-live';
import { isObject, toText, unwrapResponse } from '@/hooks/normalize-utils';

const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

export interface AwardAiGuidance {
  featureKey: string;
  available: boolean;
  payload: Record<string, unknown> | null;
  provenance: Record<string, unknown> | null;
}

function normalizeProvenance(value: unknown): Record<string, unknown> | null {
  return isObject(value) ? value : null;
}

function normalizeAwardAiGuidance(payload: unknown): AwardAiGuidance {
  const raw = unwrapResponse(payload);
  if (!isObject(raw)) {
    throw new Error('Award guidance payload must be an object.');
  }

  const guidance = isObject(raw.ai_guidance)
    ? raw.ai_guidance
    : isObject(raw.aiGuidance)
      ? raw.aiGuidance
      : null;
  if (!isObject(guidance)) {
    throw new Error('Award guidance payload is missing ai_guidance.');
  }

  const featureKey = toText(guidance.feature_key ?? guidance.featureKey);
  if (featureKey === null) {
    throw new Error('Award guidance payload is missing feature_key.');
  }

  return {
    featureKey,
    available: guidance.available === true,
    payload: isObject(guidance.payload) ? guidance.payload : null,
    provenance: normalizeProvenance(guidance.provenance),
  };
}

export function useAwardGuidance(awardId: string, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: ['award-guidance', awardId],
    enabled: Boolean(awardId) && options?.enabled !== false && !useMocks,
    queryFn: async (): Promise<AwardAiGuidance> => {
      const data = await fetchLiveOrFail(`/awards/${encodeURIComponent(awardId)}/guidance`);
      if (data === undefined) {
        throw new Error(`Award guidance "${awardId}" is unavailable from the live API.`);
      }

      return normalizeAwardAiGuidance(data);
    },
  });
}

export { normalizeAwardAiGuidance };
