import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { createTestWrapper } from '@/test/utils';

const getMock = vi.hoisted(() => vi.fn());
const putMock = vi.hoisted(() => vi.fn());

vi.mock('@/lib/api', () => ({
  api: {
    get: getMock,
    put: putMock,
  },
}));

describe('useNormalizationReview (live mode)', () => {
  const originalMocks = process.env.NEXT_PUBLIC_USE_MOCKS;

  beforeEach(() => {
    process.env.NEXT_PUBLIC_USE_MOCKS = 'false';
    getMock.mockReset();
    putMock.mockReset();
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
    const { useNormalizationReview } = await import('@/hooks/use-normalization-review');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useNormalizationReview('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('Network unavailable');
  });

  it('returns live payload when API responds with valid data', async () => {
    getMock.mockResolvedValueOnce({
      data: {
        data: [
          {
            id: 'conflict-1',
            conflict_type: 'price_mismatch',
            resolution: null,
            normalization_source_line_id: 'line-1',
          },
        ],
        meta: {
          rfq_id: 'rfq-1',
          has_blocking_issues: true,
          blocking_issue_count: 2,
        },
      },
    });
    const { useNormalizationReview } = await import('@/hooks/use-normalization-review');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useNormalizationReview('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.conflicts).toHaveLength(1);
    expect(result.current.hasBlockingIssues).toBe(true);
    expect(result.current.blockingIssueCount).toBe(2);
  });

  it('throws when the live API resolves to undefined', async () => {
    getMock.mockResolvedValueOnce({ data: undefined });
    const { useNormalizationReview } = await import('@/hooks/use-normalization-review');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useNormalizationReview('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('Normalization');
  });

  it('rejects malformed conflict payloads', async () => {
    getMock.mockResolvedValueOnce({
      data: {
        data: ['bad-row'],
        meta: {},
      },
    });
    const { useNormalizationReview } = await import('@/hooks/use-normalization-review');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useNormalizationReview('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message.toLowerCase()).toContain('conflict');
  });
});

