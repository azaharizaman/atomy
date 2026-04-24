'use client';

import { useAiNarrativeSummary } from '@/hooks/use-ai-narrative-summary';

export function useRfqAiSummary(rfqId?: string | null) {
  const normalizedRfqId = rfqId?.trim() ?? '';

  return useAiNarrativeSummary(`/risk-items?rfqId=${encodeURIComponent(normalizedRfqId)}`, 'rfq_ai_insights', {
    enabled: normalizedRfqId !== '',
    queryKey: ['rfqs', normalizedRfqId, 'ai-summary'],
  });
}
