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
      data: { data: [{ id: 'run-1', rfq_id: 'rfq-1', type: 'preview', status: 'completed' }] },
    });
    const { useComparisonRuns } = await import('./use-comparison-runs');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useComparisonRuns('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toHaveLength(1);
  });
});
