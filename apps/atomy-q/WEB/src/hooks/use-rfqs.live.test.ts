import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { createTestWrapper } from '@/test/utils';

const getMock = vi.hoisted(() => vi.fn());

vi.mock('@/lib/api', () => ({
  api: {
    get: getMock,
  },
}));

describe('useRfqs (live mode)', () => {
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
    const { useRfqs } = await import('@/hooks/use-rfqs');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useRfqs({}), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('Network unavailable');
  });

  it('returns live payload when API responds with valid pagination meta', async () => {
    getMock.mockResolvedValueOnce({
      data: {
        data: [
          {
            id: 'rfq-1',
            title: 'Live RFQ',
            status: 'active',
            vendors_count: 2,
            quotes_count: 1,
          },
        ],
        meta: {
          current_page: 1,
          per_page: 20,
          total: 1,
          total_pages: 1,
        },
      },
    });
    const { useRfqs } = await import('@/hooks/use-rfqs');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useRfqs({}), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data?.items).toHaveLength(1);
    expect(result.current.data?.items[0]?.id).toBe('rfq-1');
    expect(result.current.data?.meta.total).toBe(1);
  });

  it('surfaces an error when API pagination meta is malformed', async () => {
    getMock.mockResolvedValueOnce({
      data: {
        data: [
          {
            id: 'rfq-1',
            title: 'Live RFQ',
            status: 'active',
            vendors_count: 2,
            quotes_count: 1,
          },
        ],
        meta: {
          current_page: 1,
          per_page: 20,
          total: '1',
        },
      },
    });
    const { useRfqs } = await import('@/hooks/use-rfqs');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useRfqs({}), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
  });

  it('surfaces an error when the live API resolves to undefined', async () => {
    getMock.mockResolvedValueOnce({ data: undefined });
    const { useRfqs } = await import('@/hooks/use-rfqs');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useRfqs({}), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
  });
});
