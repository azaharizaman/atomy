import React from 'react';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import { renderWithProviders } from '@/test/utils';
import { QueryClient } from '@tanstack/react-query';

vi.mock('@/hooks/use-rfq', () => ({
  useRfq: vi.fn(() => ({
    data: { title: 'New Requisition', status: 'draft' },
    isLoading: false,
    isError: false,
    error: null,
  })),
}));

vi.mock('@/hooks/use-rfq-line-items', () => ({
  useRfqLineItems: vi.fn(() => ({
    data: [],
    isLoading: false,
    isError: false,
    error: null,
  })),
}));

vi.mock('@/hooks/use-create-rfq-line-item', () => ({
  useCreateRfqLineItem: vi.fn(() => ({
    mutateAsync: vi.fn().mockResolvedValue({}),
    isPending: false,
  })),
}));

vi.mock('@tanstack/react-query', async () => {
  const actual = await vi.importActual('@tanstack/react-query');
  return {
    ...actual,
    useQueryClient: () => new QueryClient(),
  };
});

import { RfqLineItemsPageContent } from './page';

describe('RfqLineItemsPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('shows add line item action for draft RFQs in header and opens the drawer', async () => {
    renderWithProviders(<RfqLineItemsPageContent rfqId="rfq-new-1" />);

    const headerButton = screen.getAllByRole('button', { name: /add line item/i })[0];
    expect(headerButton).toBeInTheDocument();

    fireEvent.click(headerButton);

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: /add line item/i })).toBeInTheDocument();
    });
  });

  it('shows add line item in empty state for draft RFQs and opens the drawer', async () => {
    renderWithProviders(<RfqLineItemsPageContent rfqId="rfq-new-1" />);

    const emptyStateButton = screen.getAllByRole('button', { name: /add line item/i })[1];
    expect(emptyStateButton).toBeInTheDocument();

    fireEvent.click(emptyStateButton);

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: /add line item/i })).toBeInTheDocument();
    });
  });
});