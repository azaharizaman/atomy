import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { createTestWrapper } from '@/test/utils';

const getMock = vi.hoisted(() => vi.fn());

vi.mock('@/lib/api', () => ({
  api: { get: getMock },
}));

vi.mock('@/data/seed', () => ({
  getSeedComparisonRunsByRfqId: vi.fn(() => []),
}));

describe('useComparisonRuns (live mode)', () => {
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
    const { useComparisonRuns } = await import('./use-comparison-runs');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useComparisonRuns('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('Network unavailable');
  });

  it('returns live payload when API succeeds', async () => {
    getMock.mockResolvedValueOnce({
      data: {
        data: [
          {
            id: 'run-1',
            rfq_id: 'rfq-1',
            type: 'preview',
            status: 'completed',
            name: 'Preview comparison',
            created_at: '2026-04-06T08:00:00Z',
          },
        ],
      },
    });
    const { useComparisonRuns } = await import('./use-comparison-runs');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useComparisonRuns('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toHaveLength(1);
  });

  it('rejects malformed live envelopes when data is not an array', async () => {
    getMock.mockResolvedValueOnce({
      data: { data: { id: 'run-1' } },
    });
    const { useComparisonRuns } = await import('./use-comparison-runs');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useComparisonRuns('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('data array');
  });

  it('rejects malformed live rows without an explicit run type', async () => {
    getMock.mockResolvedValueOnce({
      data: {
        data: [
          {
            id: 'run-1',
            rfq_id: 'rfq-1',
            status: 'completed',
            name: 'Unknown comparison',
            created_at: '2026-04-06T08:00:00Z',
          },
        ],
      },
    });
    const { useComparisonRuns } = await import('./use-comparison-runs');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useComparisonRuns('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('type');
  });

  it.each([
    {
      label: 'missing',
      row: { id: 'run-1', rfq_id: 'rfq-1', type: 'preview', status: 'completed', name: 'Missing date' },
    },
    {
      label: 'blank',
      row: { id: 'run-1', rfq_id: 'rfq-1', type: 'preview', status: 'completed', name: 'Blank date', created_at: '   ' },
    },
  ])('rejects live rows with $label date values', async ({ row }) => {
    getMock.mockResolvedValueOnce({
      data: {
        data: [row],
      },
    });
    const { useComparisonRuns } = await import('./use-comparison-runs');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useComparisonRuns('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('date');
  });
});
