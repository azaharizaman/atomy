import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { createTestWrapper } from '@/test/utils';

const getMock = vi.hoisted(() => vi.fn());

vi.mock('@/lib/api', () => ({
  api: {
    get: getMock,
  },
}));

describe('useRfq (live mode)', () => {
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
    const { useRfq } = await import('@/hooks/use-rfq');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useRfq('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('Network unavailable');
  });

  it('returns live payload when API responds with valid data', async () => {
    getMock.mockResolvedValueOnce({
      data: {
        id: 'rfq-1',
        title: 'Live RFQ',
        status: 'active',
        vendors_count: 2,
        quotes_count: 1,
      },
    });
    const { useRfq } = await import('@/hooks/use-rfq');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useRfq('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data?.id).toBe('rfq-1');
    expect(result.current.data?.title).toBe('Live RFQ');
  });

  it('throws when the live API resolves to undefined', async () => {
    getMock.mockResolvedValueOnce({ data: undefined });
    const { useRfq } = await import('@/hooks/use-rfq');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useRfq('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('RFQ');
  });

  it('rejects malformed live payloads that omit an id', async () => {
    getMock.mockResolvedValueOnce({
      data: {
        title: 'Malformed RFQ',
        status: 'active',
      },
    });
    const { useRfq } = await import('@/hooks/use-rfq');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useRfq('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('id');
  });
});
