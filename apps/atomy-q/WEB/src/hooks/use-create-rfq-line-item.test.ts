import { describe, expect, it, vi } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { createTestWrapper } from '@/test/utils';
import { useCreateRfqLineItem } from './use-create-rfq-line-item';

const { mockRfQStoreLineItem } = vi.hoisted(() => ({
  mockRfQStoreLineItem: vi.fn(),
}));

vi.mock('@/generated/api', () => ({
  rfqStoreLineItem: (...args: unknown[]) => mockRfQStoreLineItem(...args),
}));

describe('useCreateRfqLineItem', () => {
  it('calls the generated create mutation with the rfq id and payload', async () => {
    mockRfQStoreLineItem.mockResolvedValue({
      data: { id: 'li-1', rfq_id: 'rfq-1' },
    });

    const { Wrapper } = createTestWrapper();
    const { result } = renderHook(() => useCreateRfqLineItem('rfq-1'), { wrapper: Wrapper });

    await result.current.mutateAsync({
      description: 'Nitrogen compressor',
      quantity: 2,
      uom: 'ea',
      unit_price: 1200,
      currency: 'USD',
      specifications: 'Spare set',
    });

    await waitFor(() => expect(mockRfQStoreLineItem).toHaveBeenCalled());
    expect(mockRfQStoreLineItem).toHaveBeenCalledWith(
      expect.objectContaining({
        path: { rfqId: 'rfq-1' },
        body: expect.objectContaining({
          description: 'Nitrogen compressor',
          quantity: 2,
          uom: 'ea',
          unit_price: 1200,
          currency: 'USD',
          specifications: 'Spare set',
        }),
      }),
      expect.objectContaining({ throwOnError: true }),
    );
  });

  it('invalidates rfq queries after a successful create', async () => {
    mockRfQStoreLineItem.mockResolvedValue({
      data: { id: 'li-1', rfq_id: 'rfq-1' },
    });

    const { Wrapper, queryClient } = createTestWrapper();
    const invalidateSpy = vi.spyOn(queryClient, 'invalidateQueries');
    const { result } = renderHook(() => useCreateRfqLineItem('rfq-1'), { wrapper: Wrapper });

    await result.current.mutateAsync({
      description: 'Nitrogen compressor',
      quantity: 2,
      uom: 'ea',
      unit_price: 1200,
      currency: 'USD',
      specifications: null,
    });

    await waitFor(() => expect(invalidateSpy).toHaveBeenCalledWith({ queryKey: ['rfqs', 'rfq-1'] }));
    expect(invalidateSpy).toHaveBeenCalledWith({ queryKey: ['rfqs', 'rfq-1', 'line-items'] });
  });
});
