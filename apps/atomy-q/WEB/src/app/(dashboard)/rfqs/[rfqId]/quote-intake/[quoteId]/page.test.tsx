import React from 'react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import { renderPageWithProviders } from '@/test/utils';

const pushMock = vi.fn();
const mockUseAiStatus = vi.fn();
const mockUseQuoteSubmission = vi.fn();
const acceptQuoteMock = vi.fn();
const reparseQuoteMock = vi.fn();

vi.mock('next/navigation', () => ({
  useRouter: () => ({ push: pushMock }),
}));

vi.mock('@/hooks/use-ai-status', () => ({
  useAiStatus: () => mockUseAiStatus(),
}));

vi.mock('@/hooks/use-quote-submission', () => ({
  useQuoteSubmission: (...args: unknown[]) => mockUseQuoteSubmission(...args),
  useQuoteSubmissionActions: () => ({
    acceptQuote: {
      mutateAsync: acceptQuoteMock,
      isPending: false,
    },
    reparseQuote: {
      mutateAsync: reparseQuoteMock,
      isPending: false,
    },
  }),
}));

import QuoteIntakeDetailPage from './page';

describe('QuoteIntakeDetailPage actions', () => {
  beforeEach(() => {
    vi.clearAllMocks();

    mockUseAiStatus.mockReturnValue({
      shouldShowUnavailableMessage: () => false,
      shouldHideAiControls: () => false,
    });

    mockUseQuoteSubmission.mockReturnValue({
      data: {
        id: 'quote-1',
        rfq_id: 'rfq-1',
        vendor_id: 'vendor-1',
        vendor_name: 'Atlas Copco Compressors',
        original_filename: 'Quote_Atlas_Copco_Compressors.pdf',
        status: 'needs_review',
        blocking_issue_count: 0,
        confidence: 65,
        submitted_at: '2026-05-01T00:00:00Z',
      },
      isLoading: false,
      isError: false,
      error: null,
    });
  });

  it('shows only supported bottom actions', async () => {
    await renderPageWithProviders(
      <QuoteIntakeDetailPage params={Promise.resolve({ rfqId: 'rfq-1', quoteId: 'quote-1' })} />,
    );

    expect(await screen.findByRole('button', { name: 'Accept' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Normalize' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Re-Parse' })).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'Reject' })).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'Replace Document' })).not.toBeInTheDocument();
  });

  it('accepts a quote submission through the live status action', async () => {
    const user = userEvent.setup();
    acceptQuoteMock.mockResolvedValueOnce({});

    await renderPageWithProviders(
      <QuoteIntakeDetailPage params={Promise.resolve({ rfqId: 'rfq-1', quoteId: 'quote-1' })} />,
    );

    await user.click(await screen.findByRole('button', { name: 'Accept' }));

    await waitFor(() => expect(acceptQuoteMock).toHaveBeenCalledTimes(1));
  });

  it('opens normalize without mutating quote status', async () => {
    const user = userEvent.setup();

    await renderPageWithProviders(
      <QuoteIntakeDetailPage params={Promise.resolve({ rfqId: 'rfq-1', quoteId: 'quote-1' })} />,
    );

    await user.click(await screen.findByRole('button', { name: 'Normalize' }));

    expect(pushMock).toHaveBeenCalledWith('/rfqs/rfq-1/quote-intake/quote-1/normalize');
    expect(acceptQuoteMock).not.toHaveBeenCalled();
  });

  it('reparses through the live reparse action', async () => {
    const user = userEvent.setup();
    reparseQuoteMock.mockResolvedValueOnce({});

    await renderPageWithProviders(
      <QuoteIntakeDetailPage params={Promise.resolve({ rfqId: 'rfq-1', quoteId: 'quote-1' })} />,
    );

    await user.click(await screen.findByRole('button', { name: 'Re-Parse' }));

    await waitFor(() => expect(reparseQuoteMock).toHaveBeenCalledTimes(1));
  });
});
