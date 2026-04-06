import { renderHook, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createTestWrapper } from '@/test/utils';
import { useComparisonRuns } from './use-comparison-runs';

const mockGet = vi.hoisted(() => vi.fn());

vi.mock('@/lib/api', () => ({
  api: {
    get: mockGet,
  },
}));

describe('useComparisonRuns', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    process.env.NEXT_PUBLIC_USE_MOCKS = 'false';
  });

  it('normalizes the live list row shape without synthetic fields', async () => {
    mockGet.mockResolvedValue({
      data: {
        data: [
          {
            id: 'run-1',
            rfq_id: 'rfq-1',
            name: 'Final comparison',
            status: 'final',
            is_preview: false,
            created_at: '2026-04-06T08:00:00Z',
          },
        ],
      },
    });

    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useComparisonRuns('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(mockGet).toHaveBeenCalledWith('/comparison-runs', { params: { rfq_id: 'rfq-1' } });
    expect(result.current.data).toEqual([
      {
        id: 'run-1',
        rfq_id: 'rfq-1',
        name: 'Final comparison',
        date: '2026-04-06T08:00:00Z',
        type: 'final',
        status: 'final',
        created_at: '2026-04-06T08:00:00Z',
      },
    ]);
  });

  it('rejects rows without an id', async () => {
    mockGet.mockResolvedValue({
      data: {
        data: [
          {
            rfq_id: 'rfq-1',
            name: 'Preview comparison',
            status: 'preview',
            is_preview: true,
          },
        ],
      },
    });

    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useComparisonRuns('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));

    expect(result.current.error?.message).toMatch(/id/i);
  });
});
