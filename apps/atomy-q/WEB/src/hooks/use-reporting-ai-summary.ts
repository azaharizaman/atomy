'use client';

import { useAiNarrativeSummary } from '@/hooks/use-ai-narrative-summary';

export function useReportingAiSummary() {
  return useAiNarrativeSummary('/reports/kpis', 'dashboard_ai_summary', {
    queryKey: ['reporting', 'ai-summary'],
  });
}
