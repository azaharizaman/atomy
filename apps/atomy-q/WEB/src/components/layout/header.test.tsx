import React from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

import { renderWithProviders } from '@/test/utils';

const mockUsePathname = vi.fn(() => '/rfqs/rfq-1/details');
const mockGet = vi.fn();
const mockPost = vi.fn();

vi.mock('next/navigation', () => ({
  usePathname: () => mockUsePathname(),
  useRouter: () => ({ push: vi.fn() }),
}));

vi.mock('@/lib/api', () => ({
  api: {
    get: (...args: unknown[]) => mockGet(...args),
    post: (...args: unknown[]) => mockPost(...args),
  },
}));

vi.mock('@/hooks/use-ai-status', async (importOriginal) => {
  const actual = await importOriginal<typeof import('@/hooks/use-ai-status')>();
  return {
    ...actual,
    useAiStatus: () => ({
      status: {},
      error: null,
      isLoading: false,
      isReady: true,
      isError: false,
      refetch: vi.fn(),
      isFeatureAvailable: () => true,
      shouldHideAiControls: () => false,
      shouldShowUnavailableMessage: () => false,
      messageKeyForFeature: () => null,
    }),
  };
});

vi.mock('@/store/use-auth-store', () => ({
  useAuthStore: (selector: (state: { user: { name: string; email: string; tenantId: string }; logout: () => void }) => unknown) =>
    selector({
      user: { name: 'Aisyah Lim', email: 'aisyah@example.test', tenantId: 'tenant-1' },
      logout: vi.fn(),
    }),
}));

vi.mock('@/hooks/use-rfq', () => ({
  useRfq: () => ({
    data: { title: 'Desktop Purchase' },
    isLoading: false,
    isError: false,
    error: null,
  }),
}));

import { Header } from './header';

describe('Header', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockUsePathname.mockReturnValue('/rfqs/rfq-1/details');
    mockGet.mockResolvedValue({
      data: {
        feature_key: 'rfq_ai_insights',
        available: false,
        payload: { feature_key: 'rfq_ai_insights', available: false },
      },
    });
    mockPost.mockResolvedValue({
      data: {
        feature_key: 'rfq_ai_insights',
        available: true,
        payload: {
          feature_key: 'rfq_ai_insights',
          available: true,
          headline: 'Generated insight',
        },
      },
    });
  });

  it('renders semantic breadcrumb items for RFQ workspace routes', () => {
    renderWithProviders(<Header />);

    expect(screen.getByRole('navigation', { name: /breadcrumb/i })).toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'RFQs' })).toHaveAttribute('href', '/rfqs');
    expect(screen.getByText('Desktop Purchase')).toBeInTheDocument();
    expect(screen.getByText('Details')).toHaveAttribute('aria-current', 'page');
  });

  it('disables smart AI insights on routes without narrative capability', () => {
    mockUsePathname.mockReturnValue('/rfqs/rfq-1/details');

    renderWithProviders(<Header />);

    const button = screen.getByRole('button', { name: /ai insights unavailable/i });
    expect(button).toBeDisabled();
    expect(button).toHaveAttribute('title', 'AI insights unavailable on this page');
  });

  it('enables and glows on RFQ overview routes with AI insight capability', async () => {
    mockUsePathname.mockReturnValue('/rfqs/rfq-1/overview');

    renderWithProviders(<Header />);

    const button = screen.getByRole('button', { name: /generate ai insights/i });
    expect(button).toBeEnabled();
    expect(button).toHaveClass('animate-pulse');
    expect(button.className).toContain('!bg-white');
    expect(button.className).toContain('!text-purple-900');
    expect(button.className).toContain('!border-purple-300');
    await waitFor(() => expect(mockGet).toHaveBeenCalledWith('/risk-items?rfqId=rfq-1'));
  });

  it('generates insights directly when no summary exists', async () => {
    const user = userEvent.setup();
    mockUsePathname.mockReturnValue('/rfqs/rfq-1/overview');

    renderWithProviders(<Header />);
    await user.click(screen.getByRole('button', { name: /generate ai insights/i }));

    await waitFor(() => expect(mockPost).toHaveBeenCalledWith('/risk-items/generate', { rfq_id: 'rfq-1' }));
  });

  it('asks for confirmation before regenerating existing insights', async () => {
    const user = userEvent.setup();
    mockUsePathname.mockReturnValue('/rfqs/rfq-1/overview');
    mockGet.mockResolvedValue({
      data: {
        feature_key: 'rfq_ai_insights',
        available: true,
        payload: {
          feature_key: 'rfq_ai_insights',
          available: true,
          headline: 'Existing insight',
        },
      },
    });

    renderWithProviders(<Header />);
    await user.click(await screen.findByRole('button', { name: /regenerate ai insights/i }));

    expect(screen.getByRole('dialog', { name: /regenerate ai insights/i })).toBeInTheDocument();
    expect(mockPost).not.toHaveBeenCalled();

    await user.click(screen.getByRole('button', { name: /^regenerate$/i }));
    await waitFor(() => expect(mockPost).toHaveBeenCalledWith('/risk-items/generate', { rfq_id: 'rfq-1' }));
  });
});
