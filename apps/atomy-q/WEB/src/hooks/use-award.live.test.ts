import { act, renderHook, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { createTestWrapper } from '@/test/utils';

const getMock = vi.hoisted(() => vi.fn());
const postMock = vi.hoisted(() => vi.fn());

vi.mock('@/lib/api', () => ({
  api: {
    get: getMock,
    post: postMock,
  },
}));

describe('useAward (live mode)', () => {
  const originalMocks = process.env.NEXT_PUBLIC_USE_MOCKS;

  beforeEach(() => {
    process.env.NEXT_PUBLIC_USE_MOCKS = 'false';
    vi.resetModules();
    getMock.mockReset();
    postMock.mockReset();
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
    const { useAward } = await import('@/hooks/use-award');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useAward('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('Network unavailable');
  });

  it('returns live payload when API responds with valid data', async () => {
    getMock.mockResolvedValueOnce({
      data: {
        data: [
          {
            id: 'award-1',
            rfq_id: 'rfq-1',
            vendor_id: 'vendor-1',
            vendor_name: 'Live Vendor',
            status: 'signed_off',
            amount: 1000,
            currency: 'USD',
            comparison: {
              vendors: [
                {
                  vendor_id: 'vendor-1',
                  vendor_name: 'Live Vendor',
                  quote_submission_id: 'quote-1',
                },
                {
                  vendor_id: 'vendor-2',
                  vendor_name: 'Runner Up Vendor',
                  quote_submission_id: 'quote-2',
                },
              ],
            },
          },
        ],
      },
    });
    const { useAward } = await import('@/hooks/use-award');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useAward('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.awards).toHaveLength(1);
    expect(result.current.awards[0].id).toBe('award-1');
  });

  it('throws when the live API resolves to undefined', async () => {
    getMock.mockResolvedValueOnce({ data: undefined });
    const { useAward } = await import('@/hooks/use-award');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useAward('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message.toLowerCase()).toContain('award');
  });

  it('accepts existing award rows that omit comparison data', async () => {
    getMock.mockResolvedValueOnce({
      data: {
        data: [
          {
            id: 'award-1',
            rfq_id: 'rfq-1',
            vendor_id: 'vendor-1',
            vendor_name: 'Legacy Vendor',
            status: 'signed_off',
            amount: 1000,
            currency: 'USD',
          },
        ],
      },
    });

    const { useAward } = await import('@/hooks/use-award');
    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useAward('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.award?.vendor_name).toBe('Legacy Vendor');
    expect(result.current.award?.comparison).toBeNull();
  });

  it('rejects malformed live award payloads that omit required fields', async () => {
    getMock.mockResolvedValueOnce({
      data: {
        data: [
          {
            id: 'award-1',
            rfq_id: 'rfq-1',
            vendor_id: 'vendor-1',
            status: 'pending',
            amount: 1000,
          },
        ],
      },
    });

    const { useAward } = await import('@/hooks/use-award');
    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useAward('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('currency');
  });

  it.each([
    { label: 'boolean amount', amount: true },
    { label: 'array amount', amount: [1000] },
    { label: 'non-decimal string amount', amount: '12abc' },
  ])('rejects malformed live award payloads with $label', async ({ amount }) => {
    getMock.mockResolvedValueOnce({
      data: {
        data: [
          {
            id: 'award-1',
            rfq_id: 'rfq-1',
            vendor_id: 'vendor-1',
            vendor_name: 'Live Vendor',
            status: 'pending',
            amount,
            currency: 'USD',
            comparison: {
              vendors: [
                {
                  vendor_id: 'vendor-1',
                  vendor_name: 'Live Vendor',
                  quote_submission_id: 'quote-1',
                },
              ],
            },
          },
        ],
      },
    });

    const { useAward } = await import('@/hooks/use-award');
    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useAward('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('amount');
  });

  it('rejects malformed live award envelopes when data is not an array', async () => {
    getMock.mockResolvedValueOnce({
      data: {
        data: {
          id: 'award-1',
        },
      },
    });

    const { useAward } = await import('@/hooks/use-award');
    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useAward('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('data array');
  });

  it('rejects malformed live award payloads when comparison data is missing or malformed', async () => {
    getMock.mockResolvedValueOnce({
      data: {
        data: [
          {
            id: 'award-1',
            rfq_id: 'rfq-1',
            vendor_id: 'vendor-1',
            status: 'pending',
            amount: 1000,
            currency: 'USD',
            comparison: {
              vendors: [
                {
                  vendor_name: 'Runner Up Vendor',
                  quote_submission_id: 'quote-2',
                },
              ],
            },
          },
        ],
      },
    });

    const { useAward } = await import('@/hooks/use-award');
    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useAward('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('comparison vendor');
  });

  it('derives amount and currency from live comparison data before creating an award', async () => {
    getMock
      .mockResolvedValueOnce({ data: { data: [] } })
      .mockResolvedValueOnce({
        data: {
          data: {
            id: 'run-1',
            rfq_id: 'rfq-1',
            response_payload: {
              snapshot: {
                rfq_version: 1,
                normalized_lines: [
                  {
                    rfq_line_item_id: 'line-1',
                    source_description: 'Line 1',
                  },
                ],
                resolutions: [],
                currency_meta: { 'line-1': 'USD' },
                vendors: [{ vendor_id: 'vendor-1', vendor_name: 'Live Vendor' }],
              },
            },
          },
        },
      })
      .mockResolvedValueOnce({
        data: {
          data: {
            id: 'run-1',
            clusters: [
              {
                cluster_key: 'cluster-1',
                basis: 'price',
                offers: [
                  {
                    vendor_id: 'vendor-1',
                    rfq_line_id: 'line-1',
                    taxonomy_code: 'CAT-1',
                    normalized_unit_price: 125,
                    normalized_quantity: 4,
                    ai_confidence: 0.9,
                  },
                ],
                statistics: {
                  min_normalized_unit_price: 125,
                  max_normalized_unit_price: 125,
                  avg_normalized_unit_price: 125,
                },
                recommendation: {
                  recommended_vendor_id: 'vendor-1',
                  reason: 'lowest',
                },
              },
            ],
          },
        },
      });
    postMock.mockResolvedValueOnce({ data: { data: { id: 'award-1' } } });

    const { useAward } = await import('@/hooks/use-award');
    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useAward('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    await act(async () => {
      await result.current.store.mutateAsync({
        rfqId: 'rfq-1',
        comparisonRunId: 'run-1',
        vendorId: 'vendor-1',
      });
    });

    expect(postMock).toHaveBeenCalledWith('/awards', {
      rfq_id: 'rfq-1',
      comparison_run_id: 'run-1',
      vendor_id: 'vendor-1',
      amount: 500,
      currency: 'USD',
    });
  });

  it('rejects award creation when the selected vendor does not cover every finalized comparison line', async () => {
    getMock
      .mockResolvedValueOnce({ data: { data: [] } })
      .mockResolvedValueOnce({
        data: {
          data: {
            id: 'run-1',
            rfq_id: 'rfq-1',
            response_payload: {
              snapshot: {
                rfq_version: 1,
                normalized_lines: [
                  {
                    rfq_line_item_id: 'line-1',
                    source_description: 'Line 1',
                  },
                  {
                    rfq_line_item_id: 'line-2',
                    source_description: 'Line 2',
                  },
                ],
                resolutions: [],
                currency_meta: { 'line-1': 'USD', 'line-2': 'USD' },
                vendors: [{ vendor_id: 'vendor-1', vendor_name: 'Live Vendor' }],
              },
            },
          },
        },
      })
      .mockResolvedValueOnce({
        data: {
          data: {
            id: 'run-1',
            clusters: [
              {
                cluster_key: 'cluster-1',
                basis: 'price',
                offers: [
                  {
                    vendor_id: 'vendor-1',
                    rfq_line_id: 'line-1',
                    taxonomy_code: 'CAT-1',
                    normalized_unit_price: 125,
                    normalized_quantity: 4,
                    ai_confidence: 0.9,
                  },
                ],
                statistics: {
                  min_normalized_unit_price: 125,
                  max_normalized_unit_price: 125,
                  avg_normalized_unit_price: 125,
                },
                recommendation: {
                  recommended_vendor_id: 'vendor-1',
                  reason: 'lowest',
                },
              },
            ],
          },
        },
      });

    const { useAward } = await import('@/hooks/use-award');
    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useAward('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    await expect(
      result.current.store.mutateAsync({
        rfqId: 'rfq-1',
        comparisonRunId: 'run-1',
        vendorId: 'vendor-1',
      }),
    ).rejects.toThrow('complete finalized pricing evidence');
    expect(postMock).not.toHaveBeenCalled();
  });

  it('rejects award creation when finalized comparison lines are missing resolved currency metadata', async () => {
    getMock
      .mockResolvedValueOnce({ data: { data: [] } })
      .mockResolvedValueOnce({
        data: {
          data: {
            id: 'run-1',
            rfq_id: 'rfq-1',
            response_payload: {
              snapshot: {
                rfq_version: 1,
                normalized_lines: [
                  {
                    rfq_line_item_id: 'line-1',
                    source_description: 'Line 1',
                  },
                ],
                resolutions: [],
                currency_meta: {},
                vendors: [{ vendor_id: 'vendor-1', vendor_name: 'Live Vendor' }],
              },
            },
          },
        },
      })
      .mockResolvedValueOnce({
        data: {
          data: {
            id: 'run-1',
            clusters: [
              {
                cluster_key: 'cluster-1',
                basis: 'price',
                offers: [
                  {
                    vendor_id: 'vendor-1',
                    rfq_line_id: 'line-1',
                    taxonomy_code: 'CAT-1',
                    normalized_unit_price: 125,
                    normalized_quantity: 4,
                    ai_confidence: 0.9,
                  },
                ],
                statistics: {
                  min_normalized_unit_price: 125,
                  max_normalized_unit_price: 125,
                  avg_normalized_unit_price: 125,
                },
                recommendation: {
                  recommended_vendor_id: 'vendor-1',
                  reason: 'lowest',
                },
              },
            ],
          },
        },
      });

    const { useAward } = await import('@/hooks/use-award');
    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useAward('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    await expect(
      result.current.store.mutateAsync({
        rfqId: 'rfq-1',
        comparisonRunId: 'run-1',
        vendorId: 'vendor-1',
      }),
    ).rejects.toThrow('resolved currency metadata');
    expect(postMock).not.toHaveBeenCalled();
  });

  it('keeps signoff mutation errors visible to consumers', async () => {
    getMock.mockResolvedValueOnce({
      data: {
        data: [
          {
            id: 'award-1',
            rfq_id: 'rfq-1',
            vendor_id: 'vendor-1',
            vendor_name: 'Live Vendor',
            status: 'pending',
            amount: 1000,
            currency: 'USD',
            comparison: {
              vendors: [
                {
                  vendor_id: 'vendor-1',
                  vendor_name: 'Live Vendor',
                  quote_submission_id: 'quote-1',
                },
              ],
            },
          },
        ],
      },
    });
    postMock.mockRejectedValueOnce(new Error('Signoff rejected by API'));

    const { useAward } = await import('@/hooks/use-award');
    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useAward('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.award?.id).toBe('award-1'));

    await expect(result.current.signoff.mutateAsync('award-1')).rejects.toThrow('Signoff rejected by API');
  });
});
