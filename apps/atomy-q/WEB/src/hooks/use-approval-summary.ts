'use client';

import { useQuery } from '@tanstack/react-query';
import { fetchLiveOrFail } from '@/lib/api-live';
import { isObject, toText, unwrapResponse } from '@/hooks/normalize-utils';

export interface ApprovalSummary {
  featureKey: string;
  available: boolean;
  payload: Record<string, unknown> | null;
  provenance: Record<string, unknown> | null;
}

function normalizeProvenance(value: unknown): Record<string, unknown> | null {
  return isObject(value) ? value : null;
}

function normalizeApprovalSummaryPayload(payload: unknown): ApprovalSummary {
  const raw = unwrapResponse(payload);
  if (!isObject(raw)) {
    throw new Error('Approval summary payload must be an object.');
  }

  const summary = isObject(raw.ai_summary)
    ? raw.ai_summary
    : isObject(raw.aiSummary)
      ? raw.aiSummary
      : raw;

  const featureKey = toText(summary.feature_key ?? summary.featureKey);
  if (featureKey === null) {
    throw new Error('Approval summary payload is missing feature_key.');
  }

  const payloadObject = isObject(summary.payload) ? summary.payload : null;
  const provenance = normalizeProvenance(summary.provenance ?? payloadObject?.provenance);

  return {
    featureKey,
    available: summary.available === true,
    payload: payloadObject,
    provenance,
  };
}

export function useApprovalSummary(approvalId: string, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: ['approval-summary', approvalId],
    enabled: Boolean(approvalId) && options?.enabled !== false,
    queryFn: async (): Promise<ApprovalSummary> => {
      const data = await fetchLiveOrFail(`/approvals/${encodeURIComponent(approvalId)}/summary`);
      if (data === undefined) {
        throw new Error(`Approval summary "${approvalId}" is unavailable from the live API.`);
      }

      return normalizeApprovalSummaryPayload(data);
    },
  });
}

export { normalizeApprovalSummaryPayload as normalizeApprovalSummary };
