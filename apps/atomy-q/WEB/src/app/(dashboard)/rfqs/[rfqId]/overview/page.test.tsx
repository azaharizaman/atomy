import React, { Suspense } from 'react';
import { act, render, screen } from '@testing-library/react';
import { describe, expect, it, vi, beforeEach } from 'vitest';

import RfqOverviewPage from './page';

const mockUseRfqOverview = vi.fn();

vi.mock('@/hooks/use-rfq-overview', () => ({
  useRfqOverview: (...args: unknown[]) => mockUseRfqOverview(...args),
}));

describe('RfqOverviewPage', () => {
  beforeEach(() => {
    mockUseRfqOverview.mockReset();
  });

  it('renders an explicit unavailable state when overview data fails to load', async () => {
    mockUseRfqOverview.mockReturnValue({
      data: undefined,
      isLoading: false,
      isError: true,
      error: new Error('Malformed RFQ overview payload'),
    });

    await act(async () => {
      render(
        <Suspense fallback={null}>
          <RfqOverviewPage params={Promise.resolve({ rfqId: 'rfq-bad-1' })} />
        </Suspense>,
      );
    });

    expect(await screen.findByRole('heading', { name: 'Overview' })).toBeInTheDocument();
    expect(screen.getByText(/rfq overview unavailable/i)).toBeInTheDocument();
    expect(screen.getByText(/could not load rfq overview/i)).toBeInTheDocument();
    expect(screen.getByText(/malformed rfq overview payload/i)).toBeInTheDocument();
  });
});
