import { renderHook, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createTestWrapper } from '@/test/utils';

const mockGet = vi.hoisted(() => vi.fn());

vi.mock('@/lib/api', () => ({
  api: {
    get: mockGet,
  },
}));

describe('useComparisonRunReadiness', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('normalizes readiness flags and messages', async () => {
    mockGet.mockResolvedValue({
      data: {
        data: {
          id: 'run-42',
          readiness: {
            is_ready: false,
            is_preview_only: true,
            blockers: [
              { code: 'RFQ_NOT_CLOSED', message: 'Closing date has not been reached.' },
            ],
            warnings: [
              { code: 'LOW_AI_CONFIDENCE', message: 'Confidence below threshold.' },
            ],
          },
        },
      },
    });

    const { useComparisonRunReadiness } = await import('./use-comparison-run-readiness');
    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useComparisonRunReadiness('run-42'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(mockGet).toHaveBeenCalledTimes(1);
    expect(mockGet.mock.calls[0][0]).toBe('/comparison-runs/run-42/readiness');
    expect(result.current.data).toEqual({
      id: 'run-42',
      isReady: false,
      isPreviewOnly: true,
      blockers: [{ code: 'RFQ_NOT_CLOSED', message: 'Closing date has not been reached.' }],
      warnings: [{ code: 'LOW_AI_CONFIDENCE', message: 'Confidence below threshold.' }],
    });
  });

  it('rejects readiness entries without message text', async () => {
    mockGet.mockResolvedValue({
      data: {
        data: {
          id: 'run-42',
          readiness: {
            blockers: [{ code: 'RFQ_NOT_CLOSED' }],
            warnings: [],
          },
        },
      },
    });

    const { useComparisonRunReadiness } = await import('./use-comparison-run-readiness');
    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useComparisonRunReadiness('run-42'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));

    expect(result.current.error?.message).toMatch(/message/i);
  });

  it('fetches readiness for ready runs', async () => {
    mockGet.mockResolvedValueOnce({
      data: {
        data: {
          id: 'run-42',
          readiness: {
            is_ready: true,
            is_preview_only: false,
            blockers: [],
            warnings: [],
          },
        },
      },
    });

    const { useComparisonRunReadiness } = await import('./use-comparison-run-readiness');
    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useComparisonRunReadiness('run-42'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(mockGet).toHaveBeenCalledTimes(1);
    expect(result.current.data?.id).toBe('run-42');
    expect(result.current.data?.isReady).toBe(true);
    expect(result.current.data?.blockers).toHaveLength(0);
  });
});
