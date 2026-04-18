import React from 'react';
import { afterAll, beforeAll, beforeEach, describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import { renderWithProviders } from '@/test/utils';
import type { RfqLineItemRow } from '@/hooks/use-rfq-line-items';

const originalUseMocks = process.env.NEXT_PUBLIC_USE_MOCKS;

beforeAll(() => {
  process.env.NEXT_PUBLIC_USE_MOCKS = 'false';
});

afterAll(() => {
  process.env.NEXT_PUBLIC_USE_MOCKS = originalUseMocks;
});

const mockUseRfqLineItems = vi.fn();

vi.mock('@/hooks/use-rfq', () => ({
  useRfq: () => ({
    data: {
      title: 'New Requisition',
      status: 'draft',
    },
    isLoading: false,
    isError: false,
    error: null,
  }),
}));

vi.mock('@/hooks/use-rfq-line-items', () => ({
  useRfqLineItems: (...args: unknown[]) => mockUseRfqLineItems(...args),
}));

import { RfqLineItemsPageContent } from './page';

describe('RfqLineItemsPage', () => {
  beforeEach(() => {
    mockUseRfqLineItems.mockReturnValue({
      data: [],
      isLoading: false,
      isError: false,
      error: null,
    });
  });

  it('does not render unrelated canned rows for a new requisition', async () => {
    renderWithProviders(<RfqLineItemsPageContent rfqId="rfq-new-1" />);

    expect(await screen.findByText(/add line items to define the scope of this rfq/i)).toBeInTheDocument();
    expect(screen.getByText('No line items')).toBeInTheDocument();
    expect(screen.queryByText(/Dell PowerEdge R750/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/Cisco Catalyst 9300-48P/i)).not.toBeInTheDocument();
  });

  it('renders grouped mock-mode section rows', async () => {
    const mockRows: RfqLineItemRow[] = [
      {
        id: 'rfq-new-1-sec-1',
        rfq_id: 'rfq-new-1',
        description: 'Core scope',
        quantity: 0,
        uom: '',
        unit_price: 0,
        currency: 'USD',
        specifications: null,
        sort_order: 1,
        rowType: 'heading',
        section: 'Core scope',
      },
      {
        id: 'rfq-new-1-li-1',
        rfq_id: 'rfq-new-1',
        description: 'Core platform licenses',
        quantity: 12,
        uom: 'seats',
        unit_price: 1500,
        currency: 'USD',
        specifications: 'Annual subscription coverage',
        sort_order: 2,
        rowType: 'line',
        section: 'Core scope',
      },
    ];

    mockUseRfqLineItems.mockReturnValue({
      data: mockRows,
      isLoading: false,
      isError: false,
      error: null,
    });

    renderWithProviders(<RfqLineItemsPageContent rfqId="rfq-new-1" />);

    expect(await screen.findByText('Core scope')).toBeInTheDocument();
    expect(screen.getByText('Core platform licenses')).toBeInTheDocument();
  });
});
