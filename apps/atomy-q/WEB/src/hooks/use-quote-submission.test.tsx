import { describe, expect, it } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import React from 'react';
import { useQuoteSubmission } from './use-quote-submission';

function wrapper({ children }: { children: React.ReactNode }) {
  const client = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  return <QueryClientProvider client={client}>{children}</QueryClientProvider>;
}

describe('useQuoteSubmission', () => {
  it('does not fetch when disabled', () => {
    const { result } = renderHook(() => useQuoteSubmission('q1', { enabled: false }), { wrapper });
    expect(result.current.fetchStatus).toBe('idle');
  });

  it('returns mock shape when mocks enabled', async () => {
    const prev = process.env.NEXT_PUBLIC_USE_MOCKS;
    process.env.NEXT_PUBLIC_USE_MOCKS = 'true';
    const { result } = renderHook(() => useQuoteSubmission('quote-1'), { wrapper });
    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data?.id).toBe('quote-1');
    process.env.NEXT_PUBLIC_USE_MOCKS = prev;
  });
});
