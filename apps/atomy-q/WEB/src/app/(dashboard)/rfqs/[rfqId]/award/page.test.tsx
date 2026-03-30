import React from 'react';
import { beforeAll, describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import { renderWithProviders } from '@/test/utils';

beforeAll(() => {
  process.env.NEXT_PUBLIC_USE_MOCKS = 'false';
});

vi.mock('next/navigation', () => ({
  useRouter: () => ({ push: vi.fn() }),
}));

vi.mock('@/hooks/use-rfq', () => ({
  useRfq: () => ({ data: { title: 'RFQ', rfq_number: 'RFQ-1' } }),
}));

vi.mock('@/hooks/use-award', () => ({
  useAward: () => ({
    award: {
      id: 'award-1',
      rfq_id: 'rfq-1',
      rfq_title: 'RFQ',
      rfq_number: 'RFQ-1',
      comparison_run_id: 'run-1',
      vendor_id: 'vendor-1',
      vendor_name: 'Winner Vendor',
      status: 'pending',
      amount: '1000.00',
      currency: 'USD',
      split_details: [],
      protest_id: null,
      signoff_at: null,
      signed_off_by: null,
    },
    awards: [],
    debrief: { mutate: vi.fn(), isPending: false, isError: false },
    signoff: { mutate: vi.fn(), isPending: false, isError: false },
  }),
}));

import { RfqAwardPageContent } from './page';

describe('RfqAwardPage', () => {
  it('renders live award data and action buttons', async () => {
    renderWithProviders(<RfqAwardPageContent rfqId="rfq-1" />);

    expect(await screen.findByText('Winner Vendor', { selector: 'p' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /finalize award/i })).toBeEnabled();
    expect(screen.getByText('Non-winning vendors are resolved from the frozen comparison snapshot.')).toBeInTheDocument();
  });
});
