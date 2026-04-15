import { renderHook, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createTestWrapper } from '@/test/utils';
import { useComparisonRunMatrix } from './use-comparison-run-matrix';

const mockGet = vi.hoisted(() => vi.fn());

vi.mock('@/lib/api', () => ({
  api: {
    get: mockGet,
  },
}));

describe('useComparisonRunMatrix', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    process.env.NEXT_PUBLIC_USE_MOCKS = 'false';
  });

  it('normalizes matrix clusters from the live API', async () => {
    mockGet.mockResolvedValue({
      data: {
        data: {
          id: 'run-42',
          matrix: {
            tenant_id: 'tenant-1',
            rfq_id: 'rfq-9',
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

    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useComparisonRunMatrix('run-42'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(mockGet).toHaveBeenCalledTimes(1);
    expect(mockGet.mock.calls[0][0]).toBe('/comparison-runs/run-42/matrix');
    expect(result.current.data).toEqual({
      id: 'run-42',
      clusters: [
        {
          clusterKey: 'rfq:line-1',
          basis: 'rfq_line_id',
          offers: [
            {
              vendorId: 'vendor-1',
              rfqLineId: 'line-1',
              taxonomyCode: 'PUMP',
              normalizedUnitPrice: 110.5,
              normalizedQuantity: 2,
              aiConfidence: 0.93,
            },
          ],
          statistics: {
            minNormalizedUnitPrice: 110.5,
            maxNormalizedUnitPrice: 110.5,
            avgNormalizedUnitPrice: 110.5,
          },
          recommendation: {
            recommendedVendorId: 'vendor-1',
            reason: 'lowest_normalized_unit_price',
          },
        },
      ],
    });
  });

  it('rejects a malformed matrix response', async () => {
    mockGet.mockResolvedValue({
      data: {
        data: {
          id: 'run-42',
          matrix: {
            clusters: [{}],
          },
        },
      },
    });

    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useComparisonRunMatrix('run-42'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));

    expect(result.current.error?.message).toMatch(/cluster/i);
  });

  it('rejects a matrix offer with a non-numeric price', async () => {
    mockGet.mockResolvedValue({
      data: {
        data: {
          id: 'run-42',
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

    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useComparisonRunMatrix('run-42'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));

    expect(result.current.error?.message).toMatch(/normalized_unit_price/i);
  });
});
