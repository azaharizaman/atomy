'use client';

import { useAiNarrativeSummary } from '@/hooks/use-ai-narrative-summary';

export function useDashboardAiSummary() {
  return useAiNarrativeSummary('/dashboard/kpis', 'dashboard_ai_summary', {
    queryKey: ['dashboard', 'ai-summary'],
  });
}
