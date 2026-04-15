import { renderHook, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createTestWrapper } from '@/test/utils';
import { useComparisonRunReadiness } from './use-comparison-run-readiness';

const mockGet = vi.hoisted(() => vi.fn());

vi.mock('@/lib/api', () => ({
  api: {
    get: mockGet,
  },
}));

describe('useComparisonRunReadiness', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    process.env.NEXT_PUBLIC_USE_MOCKS = 'false';
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

    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useComparisonRunReadiness('run-42'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));

    expect(result.current.error?.message).toMatch(/message/i);
  });

  it('stays idle in mock mode', async () => {
    process.env.NEXT_PUBLIC_USE_MOCKS = 'true';

    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useComparisonRunReadiness('run-42'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.fetchStatus).toBe('idle'));

    expect(result.current.status).toBe('pending');
    expect(mockGet).not.toHaveBeenCalled();
  });
});
