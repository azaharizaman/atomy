import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { createTestWrapper } from '@/test/utils';

const getMock = vi.hoisted(() => vi.fn());

vi.mock('@/lib/api', () => ({
  api: {
    get: getMock,
  },
}));

describe('useNormalizationSourceLines (live mode)', () => {
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

  it('surfaces API errors to consumers', async () => {
    getMock.mockRejectedValueOnce(new Error('Network unavailable'));
    const { useNormalizationSourceLines } = await import('@/hooks/use-normalization-source-lines');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useNormalizationSourceLines('rfq-1'), { wrapper: Wrapper });

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
            quote_submission_id: 'quote-1',
            vendor_id: 'vendor-1',
            vendor_name: 'Vendor 1',
            source_description: 'Line item',
            has_blocking_issue: false,
            confidence: 'high',
          },
        ],
      },
    });
    const { useNormalizationSourceLines } = await import('@/hooks/use-normalization-source-lines');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useNormalizationSourceLines('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toHaveLength(1);
    expect(result.current.data?.[0].id).toBe('line-1');
  });

  it('surfaces an error when the live API resolves to undefined', async () => {
    getMock.mockResolvedValueOnce({ data: undefined });
    const { useNormalizationSourceLines } = await import('@/hooks/use-normalization-source-lines');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useNormalizationSourceLines('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('Normalization');
  });

  it('rejects malformed source line rows', async () => {
    getMock.mockResolvedValueOnce({
      data: {
        data: ['bad-row'],
      },
    });
    const { useNormalizationSourceLines } = await import('@/hooks/use-normalization-source-lines');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useNormalizationSourceLines('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('expected object');
  });
});
