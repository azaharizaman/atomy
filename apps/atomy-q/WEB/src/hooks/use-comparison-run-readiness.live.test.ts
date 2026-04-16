import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { createTestWrapper } from '@/test/utils';

const getMock = vi.hoisted(() => vi.fn());

vi.mock('@/lib/api', () => ({
  api: {
    get: getMock,
  },
}));

describe('useComparisonRunReadiness (live mode)', () => {
  let originalMocks: string | undefined;

  beforeEach(() => {
    originalMocks = process.env.NEXT_PUBLIC_USE_MOCKS;
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
    const { useComparisonRunReadiness } = await import('./use-comparison-run-readiness');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useComparisonRunReadiness('run-1', { rfqId: 'rfq-1' }), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('Network unavailable');
  });

  it('returns live payload when API responds with valid data', async () => {
    getMock.mockResolvedValueOnce({
      data: {
        data: {
          id: 'run-1',
          readiness: {
            is_ready: true,
            is_preview_only: false,
            blockers: [],
            warnings: [
              { code: 'LOW_AI_CONFIDENCE', message: 'Confidence below threshold.' },
            ],
          },
        },
      },
    });
    const { useComparisonRunReadiness } = await import('./use-comparison-run-readiness');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useComparisonRunReadiness('run-1', { rfqId: 'rfq-1' }), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data?.isReady).toBe(true);
    expect(result.current.data?.warnings).toHaveLength(1);
  });

  it('surfaces an error when the live API resolves to undefined', async () => {
    getMock.mockResolvedValueOnce({ data: undefined });
    const { useComparisonRunReadiness } = await import('./use-comparison-run-readiness');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useComparisonRunReadiness('run-1', { rfqId: 'rfq-1' }), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('Comparison readiness');
  });

  it('rejects malformed readiness payloads', async () => {
    getMock.mockResolvedValueOnce({
      data: {
        data: {
          id: 'run-1',
          readiness: {
            is_ready: true,
            is_preview_only: false,
            blockers: [{ code: 'RFQ_NOT_CLOSED' }],
            warnings: [],
          },
        },
      },
    });
    const { useComparisonRunReadiness } = await import('./use-comparison-run-readiness');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useComparisonRunReadiness('run-1', { rfqId: 'rfq-1' }), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toBe(
      'Comparison readiness blocker at index 0 is missing code or message.',
    );
  });
});
