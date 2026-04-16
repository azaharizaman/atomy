import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { createTestWrapper } from '@/test/utils';

const getMock = vi.hoisted(() => vi.fn());

vi.mock('@/lib/api', () => ({
  api: {
    get: getMock,
  },
}));

describe('useComparisonRun (live mode)', () => {
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
    const { useComparisonRun } = await import('@/hooks/use-comparison-run');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useComparisonRun('run-1', { rfqId: 'rfq-1' }), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('Network unavailable');
  });

  it('returns live payload when API responds with valid data', async () => {
    getMock.mockResolvedValueOnce({
      data: {
        data: {
          id: 'run-1',
          rfq_id: 'rfq-1',
          name: 'Preview comparison',
          status: 'completed',
          is_preview: true,
        },
      },
    });
    const { useComparisonRun } = await import('@/hooks/use-comparison-run');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useComparisonRun('run-1', { rfqId: 'rfq-1' }), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data?.id).toBe('run-1');
    expect(result.current.data?.rfqId).toBe('rfq-1');
  });

  it('throws when the live API resolves to undefined', async () => {
    getMock.mockResolvedValueOnce({ data: undefined });
    const { useComparisonRun } = await import('@/hooks/use-comparison-run');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useComparisonRun('run-1', { rfqId: 'rfq-1' }), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('Comparison');
  });

  it('rejects malformed run payloads that omit required fields', async () => {
    getMock.mockResolvedValueOnce({
      data: {
        data: {
          rfq_id: 'rfq-1',
          name: 'Malformed comparison',
          status: 'completed',
        },
      },
    });
    const { useComparisonRun } = await import('@/hooks/use-comparison-run');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useComparisonRun('run-1', { rfqId: 'rfq-1' }), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('id');
  });
});

