import { beforeAll, describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import { renderWithProviders } from '@/test/utils';

beforeAll(() => {
  process.env.NEXT_PUBLIC_USE_MOCKS = 'false';
});

vi.mock('@/hooks/use-rfq', () => ({
  useRfq: () => ({
    data: { title: 'RFQ' },
    isLoading: false,
    isError: false,
    error: null,
  }),
}));

vi.mock('@/hooks/use-rfq-vendors', () => ({
  useRfqVendors: () => ({
    data: [],
    isLoading: false,
    isError: true,
    error: new Error('Live vendor roster unavailable'),
  }),
}));

import { RfqVendorsPageContent } from './page';

describe('RfqVendorsPage', () => {
  it('renders an explicit unavailable state when vendor data fails to load', async () => {
    renderWithProviders(<RfqVendorsPageContent rfqId="rfq-1" />);

    expect(await screen.findByText(/could not load invited vendors/i)).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'Vendor roster unavailable' })).toBeInTheDocument();
    expect(screen.queryByText(/no vendors invited yet/i)).not.toBeInTheDocument();
    expect(screen.getByText(/live vendor roster unavailable/i)).toBeInTheDocument();
  });
});
