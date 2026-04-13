import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { createTestWrapper } from '@/test/utils';

const getMock = vi.hoisted(() => vi.fn());

vi.mock('@/lib/api', () => ({
  api: {
    get: getMock,
  },
}));

describe('useAward (live mode)', () => {
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
    const { useAward } = await import('@/hooks/use-award');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useAward('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('Network unavailable');
  });

  it('returns live payload when API responds with valid data', async () => {
    getMock.mockResolvedValueOnce({
      data: {
        data: [
          {
            id: 'award-1',
            rfq_id: 'rfq-1',
            vendor_id: 'vendor-1',
            vendor_name: 'Live Vendor',
            status: 'signed_off',
            amount: 1000,
            currency: 'USD',
          },
        ],
      },
    });
    const { useAward } = await import('@/hooks/use-award');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useAward('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.awards).toHaveLength(1);
    expect(result.current.awards[0].id).toBe('award-1');
  });
});
