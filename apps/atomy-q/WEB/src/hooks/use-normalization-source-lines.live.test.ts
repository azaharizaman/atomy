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
      reason: 'Supplier PDF image extraction failed',
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
      reason: 'Supplier PDF image extraction failed',
    });
  });

  it('updates and deletes manual source lines through REST-shaped normalization endpoints', async () => {
    putMock.mockResolvedValueOnce({ data: { data: { id: 'line-1' } } });
    deleteMock.mockResolvedValueOnce({ data: { data: { id: 'line-1', deleted: true } } });
    const { useManualNormalizationSourceLineMutations } = await import('@/hooks/use-normalization-source-lines');
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useManualNormalizationSourceLineMutations('rfq-1'), { wrapper: Wrapper });
    result.current.updateSourceLine.mutate({
      quoteSubmissionId: 'quote-1',
      id: 'line-1',
      source_description: 'Corrected line',
      source_quantity: '3',
      source_uom: 'box',
      source_unit_price: null,
      rfq_line_item_id: 'rfq-line-1',
      note: 'Operator note',
      reason: 'Operator correction',
    });
    await waitFor(() => expect(result.current.updateSourceLine.isSuccess).toBe(true));

    result.current.deleteSourceLine.mutate({ quoteSubmissionId: 'quote-1', id: 'line-1' });
    await waitFor(() => expect(result.current.deleteSourceLine.isSuccess).toBe(true));

    expect(putMock).toHaveBeenCalledWith('/quote-submissions/quote-1/source-lines/line-1', {
      source_description: 'Corrected line',
      source_quantity: '3',
      source_uom: 'box',
      source_unit_price: null,
      rfq_line_item_id: 'rfq-line-1',
      note: 'Operator note',
      sort_order: null,
      origin: 'manual',
      reason: 'Operator correction',
    });
    expect(deleteMock).toHaveBeenCalledWith('/quote-submissions/quote-1/source-lines/line-1');
  });
});
