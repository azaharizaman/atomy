import { describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import React from 'react';
import { useQuoteSubmission, useQuoteSubmissionActions } from './use-quote-submission';

const fetchLiveOrFailMock = vi.hoisted(() => vi.fn());
const patchMock = vi.hoisted(() => vi.fn());
const postMock = vi.hoisted(() => vi.fn());

vi.mock('@/lib/api-live', () => ({
  fetchLiveOrFail: fetchLiveOrFailMock,
}));

vi.mock('@/lib/api', () => ({
  api: {
    patch: patchMock,
    post: postMock,
  },
}));

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

  it('normalizes live quote submission payloads', async () => {
    fetchLiveOrFailMock.mockResolvedValueOnce({
      data: {
        id: 'quote-1',
        rfq_id: 'rfq-1',
        vendor_id: 'vendor-1',
        vendor_name: 'Acme',
        original_filename: 'quote.pdf',
        status: 'ready',
        blocking_issue_count: 0,
        confidence: 92,
      },
    });

    const { result } = renderHook(() => useQuoteSubmission('quote-1'), { wrapper });
    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(fetchLiveOrFailMock).toHaveBeenCalledWith('/quote-submissions/quote-1');
    expect(result.current.data).toEqual({
      id: 'quote-1',
      rfq_id: 'rfq-1',
      vendor_id: 'vendor-1',
      vendor_name: 'Acme',
      file_name: 'quote.pdf',
      status: 'ready',
      blocking_issue_count: 0,
      original_filename: 'quote.pdf',
      file_type: undefined,
      submitted_at: undefined,
      confidence: 92,
      line_items_count: undefined,
      warnings_count: undefined,
      errors_count: undefined,
      error_code: undefined,
      error_message: undefined,
      processing_started_at: undefined,
      processing_completed_at: undefined,
      parsed_at: undefined,
      retry_count: undefined,
    });
  });
});

describe('useQuoteSubmissionActions', () => {
  it('accepts quote submissions through the status endpoint', async () => {
    patchMock.mockResolvedValueOnce({ data: { data: { id: 'quote-1', status: 'ready' } } });

    const { result } = renderHook(() => useQuoteSubmissionActions('rfq-1', 'quote-1'), { wrapper });

    await result.current.acceptQuote.mutateAsync();

    expect(patchMock).toHaveBeenCalledWith('/quote-submissions/quote-1/status', { status: 'accepted' });
  });

  it('reparses quote submissions through the reparse endpoint', async () => {
    postMock.mockResolvedValueOnce({ data: { data: { id: 'quote-1', status: 'uploaded' } } });

    const { result } = renderHook(() => useQuoteSubmissionActions('rfq-1', 'quote-1'), { wrapper });

    await result.current.reparseQuote.mutateAsync();

    expect(postMock).toHaveBeenCalledWith('/quote-submissions/quote-1/reparse');
  });
});
