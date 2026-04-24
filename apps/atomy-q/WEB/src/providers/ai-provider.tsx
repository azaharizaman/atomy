'use client';

import React from 'react';

import { AiStatusContext, buildAiStatusContextValue, useAiStatusQuery } from '@/hooks/use-ai-status';
import { isAiQueryEnabled } from '@/lib/ai-status';

export function AiProvider({ children }: { children: React.ReactNode }) {
  const query = useAiStatusQuery();
  const enabled = isAiQueryEnabled();
  const value = React.useMemo(
    () =>
      buildAiStatusContextValue({
        data: query.data,
        error: query.error,
        isLoading: query.isLoading,
        refetch: query.refetch,
        enabled,
      }),
    [enabled, query.data, query.error, query.isLoading, query.refetch],
  );

  return <AiStatusContext.Provider value={value}>{children}</AiStatusContext.Provider>;
}
