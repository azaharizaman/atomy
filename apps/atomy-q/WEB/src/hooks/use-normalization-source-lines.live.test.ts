import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { createTestWrapper } from '@/test/utils';

const getMock = vi.hoisted(() => vi.fn());
const postMock = vi.hoisted(() => vi.fn());
const putMock = vi.hoisted(() => vi.fn());
const deleteMock = vi.hoisted(() => vi.fn());

vi.mock('@/lib/api', () => ({
  api: {
    get: getMock,
    post: postMock,
    put: putMock,
    delete: deleteMock,
  },
}));

describe('useNormalizationSourceLines (live mode)', () => {
  const originalMocks = process.env.NEXT_PUBLIC_USE_MOCKS;

  beforeEach(() => {
    process.env.NEXT_PUBLIC_USE_MOCKS = 'false';
    getMock.mockReset();
    postMock.mockReset();
    putMock.mockReset();
    deleteMock.mockReset();
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
    const { useNormalizationSourceLines } = await import('@/hooks/use-normalization-source-lines');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useNormalizationSourceLines('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('Network unavailable');
  });

  it('returns live payload when API responds with valid data', async () => {
    getMock.mockResolvedValueOnce({
      data: {
        data: [
          {
            id: 'line-1',
            quote_submission_id: 'quote-1',
            vendor_id: 'vendor-1',
            vendor_name: 'Vendor 1',
            source_description: 'Line item',
            has_blocking_issue: false,
            confidence: 'high',
            ai_confidence: '87.50',
            provider_suggested: {
              rfq_line_item_id: 'rfq-line-provider',
              quantity: '3.0000',
              uom: 'ea',
              unit_price: '14.2500',
            },
            effective_values: {
              rfq_line_item_id: 'rfq-line-effective',
              quantity: '2.0000',
              uom: 'box',
              unit_price: '12.0000',
            },
            is_buyer_overridden: true,
            latest_override: {
              reason_code: 'price_correction',
              note: 'Supplier PDF totals were wrong',
              actor_name: 'Buyer One',
              timestamp: '2026-04-26T01:00:00Z',
            },
          },
        ],
      },
    });
    const { useNormalizationSourceLines } = await import('@/hooks/use-normalization-source-lines');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useNormalizationSourceLines('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data).toHaveLength(1);
    expect(result.current.data?.[0].id).toBe('line-1');
    expect(result.current.data?.[0].provider_suggested?.rfq_line_item_id).toBe('rfq-line-provider');
    expect(result.current.data?.[0].effective_values?.rfq_line_item_id).toBe('rfq-line-effective');
    expect(result.current.data?.[0].is_buyer_overridden).toBe(true);
    expect(result.current.data?.[0].latest_override?.reason_code).toBe('price_correction');
    expect(result.current.data?.[0].ai_confidence).toBe('87.50');
  });

  it('surfaces an error when the live API resolves to undefined', async () => {
    getMock.mockResolvedValueOnce({ data: undefined });
    const { useNormalizationSourceLines } = await import('@/hooks/use-normalization-source-lines');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useNormalizationSourceLines('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('Normalization');
  });

  it('rejects malformed source line rows', async () => {
    getMock.mockResolvedValueOnce({
      data: {
        data: ['bad-row'],
      },
    });
    const { useNormalizationSourceLines } = await import('@/hooks/use-normalization-source-lines');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useNormalizationSourceLines('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('expected object');
  });

  it('creates manual source lines using the quote submission source-line endpoint', async () => {
    postMock.mockResolvedValueOnce({ data: { data: { id: 'line-new' } } });
    const { useManualNormalizationSourceLineMutations } = await import('@/hooks/use-normalization-source-lines');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useManualNormalizationSourceLineMutations('rfq-1'), { wrapper: Wrapper });
    result.current.createSourceLine.mutate({
      quoteSubmissionId: 'quote-1',
      source_description: 'Manual line',
      source_quantity: '2',
      source_uom: 'ea',
      source_unit_price: '12.50',
      rfq_line_item_id: 'rfq-line-1',
      note: 'Typed from vendor email',
      reason: 'manual_entry_required',
    });

    await waitFor(() => expect(result.current.createSourceLine.isSuccess).toBe(true));
    expect(postMock).toHaveBeenCalledWith('/quote-submissions/quote-1/source-lines', {
      source_description: 'Manual line',
      source_quantity: '2',
      source_uom: 'ea',
      source_unit_price: '12.50',
      rfq_line_item_id: 'rfq-line-1',
      note: 'Typed from vendor email',
      sort_order: null,
      origin: 'manual',
      reason: 'manual_entry_required',
    });
  });

  it('overrides and deletes manual source lines through REST-shaped normalization endpoints', async () => {
    putMock.mockResolvedValueOnce({ data: { data: { id: 'line-1' } } });
    deleteMock.mockResolvedValueOnce({ data: { data: { id: 'line-1', deleted: true } } });
    const { useManualNormalizationSourceLineMutations } = await import('@/hooks/use-normalization-source-lines');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useManualNormalizationSourceLineMutations('rfq-1'), { wrapper: Wrapper });
    result.current.overrideSourceLine.mutate({
      id: 'line-1',
      override_data: {
        rfq_line_item_id: 'rfq-line-1',
        source_description: 'Line item',
        quantity: '3',
        uom: 'box',
        unit_price: null,
      },
      note: 'Operator note',
      reason_code: 'price_correction',
    });
    await waitFor(() => expect(result.current.overrideSourceLine.isSuccess).toBe(true));

    result.current.deleteSourceLine.mutate({ quoteSubmissionId: 'quote-1', id: 'line-1' });
    await waitFor(() => expect(result.current.deleteSourceLine.isSuccess).toBe(true));

    expect(putMock).toHaveBeenCalledWith('/normalization/source-lines/line-1/override', {
      override_data: {
        rfq_line_item_id: 'rfq-line-1',
        source_description: 'Line item',
        quantity: '3',
        uom: 'box',
        unit_price: null,
      },
      note: 'Operator note',
      reason_code: 'price_correction',
    });
    expect(deleteMock).toHaveBeenCalledWith('/quote-submissions/quote-1/source-lines/line-1');
  });
});
