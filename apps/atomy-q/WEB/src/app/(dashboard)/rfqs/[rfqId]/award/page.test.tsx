import React from 'react';
import { beforeAll, describe, expect, it, vi, beforeEach } from 'vitest';
import { fireEvent, screen } from '@testing-library/react';
import { renderWithProviders } from '@/test/utils';

import { useAward } from '@/hooks/use-award';
import { useComparisonRuns } from '@/hooks/use-comparison-runs';
import { useRfqVendors } from '@/hooks/use-rfq-vendors';

beforeAll(() => {
  process.env.NEXT_PUBLIC_USE_MOCKS = 'false';
});

vi.mock('next/navigation', () => ({
  useRouter: () => ({ push: vi.fn() }),
}));

vi.mock('@/hooks/use-rfq', () => ({
  useRfq: () => ({ data: { title: 'RFQ', rfq_number: 'RFQ-1' } }),
}));

vi.mock('@/hooks/use-award');
vi.mock('@/hooks/use-comparison-runs');
vi.mock('@/hooks/use-rfq-vendors');

const mockAward = {
  id: 'award-1',
  rfq_id: 'rfq-1',
  rfq_title: 'RFQ',
  rfq_number: 'RFQ-1',
  comparison_run_id: 'run-1',
  vendor_id: 'vendor-1',
  vendor_name: 'Winner Vendor',
  status: 'pending',
  amount: 1000.00,
  currency: 'USD',
  split_details: [],
  protest_id: null,
  signoff_at: null,
  signed_off_by: null,
  comparison: {
    vendors: [
      { vendor_id: 'vendor-1', vendor_name: 'Winner Vendor', quote_submission_id: 'quote-1' },
      { vendor_id: 'vendor-2', vendor_name: 'Other Vendor', quote_submission_id: 'quote-2' },
    ],
  },
};

import { RfqAwardPageContent } from './page';

describe('RfqAwardPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    
    vi.mocked(useAward).mockReturnValue({
      award: mockAward,
      awards: [mockAward],
      signoff: { mutate: vi.fn(), isPending: false, isError: false },
      debrief: { mutate: vi.fn(), isPending: false, isError: false },
      store: { mutate: vi.fn(), isPending: false, isError: false },
    } as any);

    vi.mocked(useComparisonRuns).mockReturnValue({
      data: [{ id: 'run-1', type: 'final', status: 'frozen' }],
    } as any);

    vi.mocked(useRfqVendors).mockReturnValue({
      data: [
        { id: 'inv-1', vendor_id: 'vendor-1', name: 'Winner Vendor', status: 'responded' },
        { id: 'inv-2', vendor_id: 'vendor-2', name: 'Other Vendor', status: 'responded' },
      ],
    } as any);
  });

  it('renders live award data and action buttons', async () => {
    renderWithProviders(<RfqAwardPageContent rfqId="rfq-1" />);

    expect(await screen.findByText('Winner Vendor', { selector: 'p' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /finalize award/i })).toBeEnabled();
  });

  it('allows sending debrief messages', async () => {
    renderWithProviders(<RfqAwardPageContent rfqId="rfq-1" />);

    const debriefInput = await screen.findByLabelText(/debrief message/i);
    const sendButton = screen.getByRole('button', { name: /send deb //send debrief/i });

    expect(sendButton).toBeDisabled();

    fireEvent.change(debriefInput, { target: { value: 'Thanks for your proposal.' } });

    expect(sendButton).toBeEnabled();
  });

  it('shows award creation UI when no award exists', async () => {
    vi.mocked(useAward).mockReturnValue({
      award: null,
      awards: [],
      signoff: { mutate: vi.fn(), isPending: false, isError: false },
      debrief: { mutate: vi.fn(), isPending: false, isError: false },
      store: { mutate: vi.fn(), isPending: false, isError: false },
    } as any);

    vi.mocked(useComparisonRuns).mockReturnValue({
      data: [{ id: 'run-1', type: 'final', status: 'frozen' }],
    } as any);

    renderWithProviders(<RfqAwardPageContent rfqId="rfq-1" />);

    expect(await screen.findByText(/Select a vendor to award/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /create award/i })).toBeInTheDocument();
  });
});
