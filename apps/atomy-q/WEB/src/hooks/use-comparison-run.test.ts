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

    expect(mockGet).toHaveBeenCalledWith('/comparison-runs/run-42');
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
});
