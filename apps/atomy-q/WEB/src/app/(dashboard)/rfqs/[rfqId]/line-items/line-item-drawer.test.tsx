import React from 'react';
import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen } from '@testing-library/react';
import { renderWithProviders } from '@/test/utils';

const mockMutateAsync = vi.fn();

vi.mock('@/hooks/use-create-rfq-line-item', () => ({
  useCreateRfqLineItem: () => ({
    mutateAsync: mockMutateAsync,
    isPending: false,
    error: null,
  }),
}));

import { LineItemDrawer } from './line-item-drawer';

describe('LineItemDrawer', () => {
  it('submits the create form and closes on success', async () => {
    mockMutateAsync.mockResolvedValueOnce({ data: { id: 'li-1' } });
    const onClose = vi.fn();
    const onCreated = vi.fn();

    renderWithProviders(<LineItemDrawer rfqId="rfq-1" onClose={onClose} onCreated={onCreated} isWritable open />);

    fireEvent.change(screen.getByLabelText(/description/i), { target: { value: 'Nitrogen compressor' } });
    fireEvent.change(screen.getByLabelText(/quantity/i), { target: { value: '2' } });
    fireEvent.change(screen.getByLabelText(/uom/i), { target: { value: 'ea' } });
    fireEvent.change(screen.getByLabelText(/unit price/i), { target: { value: '1200' } });
    fireEvent.change(screen.getByLabelText(/currency/i), { target: { value: 'USD' } });
    await fireEvent.click(screen.getByRole('button', { name: /save line item/i }));

    expect(mockMutateAsync).toHaveBeenCalledWith(
      expect.objectContaining({
        description: 'Nitrogen compressor',
        quantity: 2,
        uom: 'ea',
        unit_price: 1200,
        currency: 'USD',
      }),
    );
    await expect(onCreated).toHaveBeenCalled();
    await expect(onClose).toHaveBeenCalled();
  });
});