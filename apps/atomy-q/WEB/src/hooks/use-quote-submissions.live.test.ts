import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { createTestWrapper } from '@/test/utils';

const getMock = vi.hoisted(() => vi.fn());

vi.mock('@/lib/api', () => ({
  api: { get: getMock },
}));

describe('useQuoteSubmissions (live mode)', () => {
  const originalMocks = process.env.NEXT_PUBLIC_USE_MOCKS;

  beforeEach(() => {
    process.env.NEXT_PUBLIC_USE_MOCKS = 'false';
    getMock.mockReset();
  });

  afterEach(() => {
    if (originalMocks === undefined) {
      delete process.env.NEXT_PUBLIC_USE_MOCKS;
    } else {
      process.env.NEXT_PUBLIC_USE_MOCKS = originalMocks;
    }
  });

  it('surfaces API errors instead of silently falling back to seed data', async () => {
    getMock.mockRejectedValueOnce(new Error('Network unavailable'));
    const { useQuoteSubmissions } = await import('./use-quote-submissions');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useQuoteSubmissions('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('Network unavailable');
  });

  it('returns live payload when API succeeds', async () => {
    getMock.mockResolvedValueOnce({
      data: {
        data: [
          {
            id: 'quote-1',
            rfq_id: 'rfq-1',
            vendor_id: 'vendor-1',
            vendor_name: 'Vendor 1',
            file_name: 'quote.pdf',
            status: 'ready',
            confidence: 'high',
            submitted_at: '2024-01-01T00:00:00Z',
            blocking_issue_count: 0,
          },
        ],
      },
    });
    const { useQuoteSubmissions } = await import('./use-quote-submissions');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useQuoteSubmissions('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toHaveLength(1);
  });

  it('throws when the live API resolves to undefined', async () => {
    getMock.mockResolvedValueOnce({ data: undefined });
    const { useQuoteSubmissions } = await import('./use-quote-submissions');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useQuoteSubmissions('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('Quote');
  });

  it('rejects malformed quote submission rows', async () => {
    getMock.mockResolvedValueOnce({
      data: {
        data: [
          {
            id: 'quote-1',
            rfq_id: 'rfq-1',
            vendor_id: 'vendor-1',
            vendor_name: 'Vendor 1',
            file_name: 'quote.pdf',
            status: 'ready',
            submitted_at: '2024-01-01T00:00:00Z',
            blocking_issue_count: 0,
          },
        ],
      },
    });
    const { useQuoteSubmissions } = await import('./use-quote-submissions');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useQuoteSubmissions('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('confidence');
  });
});
