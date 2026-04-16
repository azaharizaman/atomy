import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { createTestWrapper } from '@/test/utils';

const getMock = vi.hoisted(() => vi.fn());

vi.mock('@/lib/api', () => ({
  api: {
    get: getMock,
  },
}));

describe('useComparisonRunMatrix (live mode)', () => {
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
    const { useComparisonRunMatrix } = await import('./use-comparison-run-matrix');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useComparisonRunMatrix('run-1', { rfqId: 'rfq-1' }), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('Network unavailable');
  });

  it('returns live payload when API responds with valid data', async () => {
    getMock.mockResolvedValueOnce({
      data: {
        data: {
          id: 'run-1',
          matrix: {
            clusters: [
              {
                cluster_key: 'rfq:line-1',
                basis: 'rfq_line_id',
                offers: [
                  {
                    vendor_id: 'vendor-1',
                    rfq_line_id: 'line-1',
                    taxonomy_code: 'PUMP',
                    normalized_unit_price: 110.5,
                    normalized_quantity: 2,
                    ai_confidence: 0.93,
                  },
                ],
                statistics: {
                  min_normalized_unit_price: 110.5,
                  max_normalized_unit_price: 110.5,
                  avg_normalized_unit_price: 110.5,
                },
                recommendation: {
                  recommended_vendor_id: 'vendor-1',
                  reason: 'lowest_normalized_unit_price',
                },
              },
            ],
          },
        },
      },
    });
    const { useComparisonRunMatrix } = await import('./use-comparison-run-matrix');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useComparisonRunMatrix('run-1', { rfqId: 'rfq-1' }), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data?.clusters).toHaveLength(1);
  });

  it('throws when the live API resolves to undefined', async () => {
    getMock.mockResolvedValueOnce({ data: undefined });
    const { useComparisonRunMatrix } = await import('./use-comparison-run-matrix');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useComparisonRunMatrix('run-1', { rfqId: 'rfq-1' }), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('Comparison matrix');
  });

  it('rejects malformed matrix payloads', async () => {
    getMock.mockResolvedValueOnce({
      data: {
        data: {
          id: 'run-1',
          matrix: {
            clusters: [
              {
                cluster_key: 'rfq:line-1',
                basis: 'rfq_line_id',
                offers: [
                  {
                    vendor_id: 'vendor-1',
                    rfq_line_id: 'line-1',
                    taxonomy_code: 'PUMP',
                    normalized_unit_price: 'zero',
                    normalized_quantity: 2,
                    ai_confidence: 0.93,
                  },
                ],
                statistics: {
                  min_normalized_unit_price: 110.5,
                  max_normalized_unit_price: 110.5,
                  avg_normalized_unit_price: 110.5,
                },
                recommendation: {
                  recommended_vendor_id: 'vendor-1',
                  reason: 'lowest_normalized_unit_price',
                },
              },
            ],
          },
        },
      },
    });
    const { useComparisonRunMatrix } = await import('./use-comparison-run-matrix');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useComparisonRunMatrix('run-1', { rfqId: 'rfq-1' }), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('normalized_unit_price');
  });
});
