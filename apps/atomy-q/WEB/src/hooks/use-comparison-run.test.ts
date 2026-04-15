import { renderHook, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createTestWrapper } from '@/test/utils';
import { useComparisonRun } from './use-comparison-run';

const mockGet = vi.hoisted(() => vi.fn());

vi.mock('@/lib/api', () => ({
  api: {
    get: mockGet,
  },
}));

describe('useComparisonRun', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    process.env.NEXT_PUBLIC_USE_MOCKS = 'false';
  });

  it('normalizes the live comparison run detail payload', async () => {
    mockGet.mockResolvedValue({
      data: {
        data: {
          id: 'run-42',
          rfq_id: 'rfq-9',
          name: 'Final comparison',
          status: 'final',
          is_preview: false,
          snapshot: {
            normalized_lines: [
              {
                rfq_line_item_id: 'line-1',
                source_description: 'Pump assembly',
              },
            ],
            vendors: [
              {
                vendor_id: 'vendor-1',
                vendor_name: 'Vendor One',
                quote_submission_id: 'quote-1',
              },
            ],
            rfq_version: 123,
            resolutions: [],
            currency_meta: {
              'line-1': 'USD',
            },
          },
          created_at: '2026-04-06T08:00:00Z',
        },
      },
    });

    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useComparisonRun('run-42'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(mockGet).toHaveBeenCalledTimes(1);
    expect(mockGet.mock.calls[0][0]).toBe('/comparison-runs/run-42');
    expect(result.current.data).toEqual({
      id: 'run-42',
      rfqId: 'rfq-9',
      name: 'Final comparison',
      status: 'final',
      isPreview: false,
      snapshot: {
        normalizedLines: [
          {
            rfqLineItemId: 'line-1',
            sourceDescription: 'Pump assembly',
            sourceLineId: null,
            quoteSubmissionId: null,
            vendorId: null,
            sourceUnitPrice: null,
            sourceUom: null,
            sourceQuantity: null,
          },
        ],
        vendors: [
          {
            vendorId: 'vendor-1',
            vendorName: 'Vendor One',
            quoteSubmissionId: 'quote-1',
          },
        ],
        rfqVersion: 123,
        resolutions: [],
        currencyMeta: {
          'line-1': 'USD',
        },
      },
      createdAt: '2026-04-06T08:00:00Z',
    });
  });

  it('rejects an invalid run detail payload', async () => {
    mockGet.mockResolvedValue({
      data: {
        data: {
          rfq_id: 'rfq-9',
          status: 'preview',
          is_preview: true,
        },
      },
    });

    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useComparisonRun('run-42'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));

    expect(result.current.error).toBeInstanceOf(Error);
    expect(result.current.error?.message).toMatch(/id/i);
  });

  it('rejects a snapshot payload with a non-numeric rfq version', async () => {
    mockGet.mockResolvedValue({
      data: {
        data: {
          id: 'run-42',
          rfq_id: 'rfq-9',
          status: 'final',
          is_preview: false,
          snapshot: {
            rfq_version: 'not-a-number',
            normalized_lines: [],
            resolutions: [],
            currency_meta: {},
            vendors: [],
          },
        },
      },
    });

    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useComparisonRun('run-42'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));

    expect(result.current.error?.message).toMatch(/rfq_version/i);
  });

  it('accepts camelCase snapshot containers and response payloads', async () => {
    mockGet.mockResolvedValue({
      data: {
        data: {
          id: 'run-42',
          rfq_id: 'rfq-9',
          name: 'Final comparison',
          status: 'final',
          isPreview: 'true',
          responsePayload: {
            snapshot: {
              normalizedLines: [],
              vendorSummaries: [
                {
                  vendorId: 'vendor-1',
                  vendorName: 'Vendor One',
                  quoteSubmissionId: 'quote-1',
                },
              ],
              rfqVersion: 123,
              resolutions: [],
              currencyMeta: {},
            },
          },
        },
      },
    });

    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useComparisonRun('run-42'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(result.current.data).toMatchObject({
      isPreview: true,
      snapshot: {
        vendors: [
          {
            vendorId: 'vendor-1',
            vendorName: 'Vendor One',
            quoteSubmissionId: 'quote-1',
          },
        ],
      },
    });
  });

  it('fails closed when the fetched run belongs to another rfq', async () => {
    mockGet.mockResolvedValue({
      data: {
        data: {
          id: 'run-42',
          rfq_id: 'rfq-9',
          status: 'final',
          is_preview: false,
          snapshot: {
            rfq_version: 123,
            normalized_lines: [],
            resolutions: [],
            currency_meta: {},
            vendors: [],
          },
        },
      },
    });

    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useComparisonRun('run-42', { rfqId: 'rfq-8' }), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));

    expect(result.current.error?.message).toMatch(/does not belong to rfq/i);
  });
});
