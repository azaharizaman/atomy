'use client';

import { useQuery } from '@tanstack/react-query';
import { fetchLiveOrFail } from '@/lib/api-live';
import { isObject, toText, unwrapResponse } from '@/hooks/normalize-utils';

const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

export interface AwardDebriefDraft {
  featureKey: string;
  available: boolean;
  payload: Record<string, unknown> | null;
  provenance: Record<string, unknown> | null;
}

function normalizeProvenance(value: unknown): Record<string, unknown> | null {
  return isObject(value) ? value : null;
}

export function normalizeAwardDebriefDraft(payload: unknown): AwardDebriefDraft {
  const raw = unwrapResponse(payload);
  if (!isObject(raw)) {
    throw new Error('Award debrief draft payload must be an object.');
  }

  const draft = isObject(raw.ai_debrief_draft)
    ? raw.ai_debrief_draft
    : isObject(raw.aiDebriefDraft)
      ? raw.aiDebriefDraft
      : null;
  if (!isObject(draft)) {
    throw new Error('Award debrief draft payload is missing ai_debrief_draft.');
  }

  const featureKey = toText(draft.feature_key ?? draft.featureKey);
  if (featureKey === null) {
    throw new Error('Award debrief draft payload is missing feature_key.');
  }

  if (typeof draft.available !== 'boolean') {
    throw new Error('Award debrief draft payload has invalid available state.');
  }

  return {
    featureKey,
    available: draft.available,
    payload: isObject(draft.payload) ? draft.payload : null,
    provenance: normalizeProvenance(draft.provenance),
  };
}

export function useAwardDebriefDraft(
  awardId: string,
  vendorId: string,
  options?: { enabled?: boolean },
) {
  return useQuery({
    queryKey: ['award-debrief-draft', awardId, vendorId],
    enabled: Boolean(awardId) && Boolean(vendorId) && options?.enabled !== false && !useMocks,
    queryFn: async (): Promise<AwardDebriefDraft> => {
      const data = await fetchLiveOrFail(
        `/awards/${encodeURIComponent(awardId)}/debrief-draft/${encodeURIComponent(vendorId)}`,
      );
      if (data === undefined) {
        throw new Error(`Award debrief draft "${awardId}/${vendorId}" is unavailable from the live API.`);
      }

      return normalizeAwardDebriefDraft(data);
    },
  });
}
