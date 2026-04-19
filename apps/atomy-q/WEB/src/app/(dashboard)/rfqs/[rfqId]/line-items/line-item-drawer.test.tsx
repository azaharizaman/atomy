import React from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { act, fireEvent, screen } from '@testing-library/react';
import { renderWithProviders } from '@/test/utils';

let mockMutateAsync: ReturnType<typeof vi.fn>;

vi.mock('@/hooks/use-create-rfq-line-item', () => ({
  useCreateRfqLineItem: () => ({
    mutateAsync: (...args: unknown[]) => mockMutateAsync(...args),
    isPending: false,
    error: null,
  }),
}));

import { LineItemDrawer } from './line-item-drawer';

describe('LineItemDrawer', () => {
  beforeEach(() => {
    mockMutateAsync = vi.fn();
  });

  afterEach(() => {
    mockMutateAsync.mockReset();
  });

  it('shows error state when submission fails', async () => {
    mockMutateAsync.mockRejectedValueOnce(new Error('Network error'));
    const onClose = vi.fn();

    renderWithProviders(<LineItemDrawer rfqId="rfq-1" onClose={onClose} isWritable open />);

    fireEvent.change(screen.getByLabelText(/description/i), { target: { value: 'Test item' } });
    fireEvent.change(screen.getByLabelText(/quantity/i), { target: { value: '1' } });
    fireEvent.change(screen.getByLabelText(/uom/i), { target: { value: 'ea' } });
    fireEvent.change(screen.getByLabelText(/unit price/i), { target: { value: '100' } });
    fireEvent.change(screen.getByLabelText(/currency/i), { target: { value: 'USD' } });
    await act(async () => {
      fireEvent.click(screen.getByRole('button', { name: /save line item/i }));
    });

    await vi.waitFor(() => expect(mockMutateAsync).toHaveBeenCalled());
    await vi.waitFor(() => {
      expect(screen.getByText(/network error/i)).toBeInTheDocument();
    });
  });

  it('blocks submission in mock mode', async () => {
    const onClose = vi.fn();

    renderWithProviders(<LineItemDrawer rfqId="rfq-1" onClose={onClose} isWritable={false} open />);

    fireEvent.change(screen.getByLabelText(/description/i), { target: { value: 'Test item' } });
    await fireEvent.click(screen.getByRole('button', { name: /save line item/i }));

    expect(mockMutateAsync).not.toHaveBeenCalled();
    expect(screen.getByText(/mock mode is read-only/i)).toBeInTheDocument();
  });

  it('rejects blank numeric fields before submit', async () => {
    const onClose = vi.fn();

    renderWithProviders(<LineItemDrawer rfqId="rfq-1" onClose={onClose} isWritable open />);

    fireEvent.change(screen.getByLabelText(/description/i), { target: { value: 'Nitrogen compressor' } });
    fireEvent.change(screen.getByLabelText(/quantity/i), { target: { value: '' } });
    fireEvent.change(screen.getByLabelText(/uom/i), { target: { value: 'ea' } });
    fireEvent.change(screen.getByLabelText(/unit price/i), { target: { value: '' } });
    fireEvent.change(screen.getByLabelText(/currency/i), { target: { value: 'USD' } });
    await fireEvent.click(screen.getByRole('button', { name: /save line item/i }));

    expect(mockMutateAsync).not.toHaveBeenCalled();
    await vi.waitFor(() => expect(screen.getByText(/quantity is required/i)).toBeInTheDocument());
  });

  it('rejects blank unit price before submit', async () => {
    const onClose = vi.fn();

    renderWithProviders(<LineItemDrawer rfqId="rfq-1" onClose={onClose} isWritable open />);

    fireEvent.change(screen.getByLabelText(/description/i), { target: { value: 'Nitrogen compressor' } });
    fireEvent.change(screen.getByLabelText(/quantity/i), { target: { value: '2' } });
    fireEvent.change(screen.getByLabelText(/uom/i), { target: { value: 'ea' } });
    fireEvent.change(screen.getByLabelText(/unit price/i), { target: { value: '' } });
    fireEvent.change(screen.getByLabelText(/currency/i), { target: { value: 'USD' } });
    await fireEvent.click(screen.getByRole('button', { name: /save line item/i }));

    expect(mockMutateAsync).not.toHaveBeenCalled();
    await vi.waitFor(() => expect(screen.getByText(/unit price is required/i)).toBeInTheDocument());
  });

  it('closes once when escape is pressed', async () => {
    const onClose = vi.fn();

    renderWithProviders(<LineItemDrawer rfqId="rfq-1" onClose={onClose} isWritable open />);

    fireEvent.keyDown(document, { key: 'Escape' });

    await vi.waitFor(() => expect(onClose).toHaveBeenCalledTimes(1));
  });

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
    expect(mockMutateAsync).toHaveBeenCalledWith(
      expect.objectContaining({
        description: expect.any(String),
      }),
    );
    await vi.waitFor(() => expect(onCreated).toHaveBeenCalled());
    await vi.waitFor(() => expect(onClose).toHaveBeenCalled());
  });
});
