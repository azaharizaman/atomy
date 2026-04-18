import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { createTestWrapper } from '@/test/utils';

const getMock = vi.hoisted(() => vi.fn());

vi.mock('@/lib/api', () => ({
  api: {
    get: getMock,
  },
}));

describe('useRfqLineItems (live mode)', () => {
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
    const { useRfqLineItems } = await import('./use-rfq-line-items');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useRfqLineItems('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('Network unavailable');
  });

  it('returns live payload when API responds with valid data', async () => {
    getMock.mockResolvedValueOnce({
      data: {
        data: [
          {
            id: 'line-1',
            rfq_id: 'rfq-1',
            description: 'Network switches',
            quantity: 8,
            uom: 'units',
            unit_price: 2500,
            currency: 'USD',
            specifications: '48-port',
            sort_order: 1,
          },
        ],
      },
    });
    const { useRfqLineItems } = await import('./use-rfq-line-items');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useRfqLineItems('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toHaveLength(1);
    expect(result.current.data?.[0].id).toBe('line-1');
    expect(result.current.data?.[0].description).toBe('Network switches');
  });

  it('throws when the live API resolves to undefined', async () => {
    getMock.mockResolvedValueOnce({ data: undefined });
    const { useRfqLineItems } = await import('./use-rfq-line-items');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useRfqLineItems('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('Line items');
  });

  it('rejects malformed line-item rows', async () => {
    getMock.mockResolvedValueOnce({
      data: {
        data: [
          {
            id: 'line-1',
            rfq_id: 'rfq-1',
            description: 'Network switches',
            quantity: 'eight',
            uom: 'units',
            unit_price: 2500,
            currency: 'USD',
            sort_order: 1,
          },
        ],
      },
    });
    const { useRfqLineItems } = await import('./use-rfq-line-items');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useRfqLineItems('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('quantity');
  });
});
