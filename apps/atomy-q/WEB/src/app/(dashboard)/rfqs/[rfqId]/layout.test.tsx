import React, { Suspense } from 'react';
import { act } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import { renderWithProviders } from '@/test/utils';

const mockUseRfq = vi.fn();
const mockUseFeatureFlags = vi.fn();

vi.mock('next/navigation', () => ({
  usePathname: () => '/rfqs/rfq-1/overview',
}));

vi.mock('@/components/layout/header', () => ({
  Header: () => <div data-testid="header" />,
}));

vi.mock('@/components/layout/app-footer', () => ({
  AppFooter: () => <div data-testid="footer" />,
}));

vi.mock('@/components/layout/main-sidebar-nav', () => ({
  MainSidebarNav: () => <div data-testid="nav" />,
}));

vi.mock('@/components/workspace/active-record-menu', () => ({
  ActiveRecordMenu: () => <div data-testid="active-record" />,
}));

vi.mock('@/components/workspace/rfq-insights-sidebar', () => ({
  RfqInsightsSidebar: () => <div data-testid="insights-sidebar" />,
}));

vi.mock('@/hooks/use-rfq', () => ({
  useRfq: (...args: unknown[]) => mockUseRfq(...args),
}));

vi.mock('@/hooks/use-feature-flags', () => ({
  useFeatureFlags: (...args: unknown[]) => mockUseFeatureFlags(...args),
}));

vi.mock('@/lib/alpha-mode', () => ({
  isAlphaMode: () => false,
}));

import RfqWorkspaceLayout from './layout';

describe('RfqWorkspaceLayout', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockUseFeatureFlags.mockReturnValue({
      data: { projects: true },
      isLoading: false,
    });
  });

  it('renders a plain-language error when the RFQ record cannot be loaded', async () => {
    mockUseRfq.mockReturnValue({
      data: undefined,
      isLoading: false,
      isError: true,
      error: new Error('Network unavailable'),
    });

    await act(async () => {
      renderWithProviders(
        <Suspense fallback={null}>
          <RfqWorkspaceLayout params={Promise.resolve({ rfqId: 'rfq-1' })}>
            <div>Child</div>
          </RfqWorkspaceLayout>
        </Suspense>,
      );
    });

    expect(screen.getAllByText('Unable to load this RFQ').length).toBeGreaterThan(0);
    expect(screen.getAllByText('Could not load this RFQ').length).toBeGreaterThan(0);
    expect(screen.getAllByText(/we could not load this rfq right now/i).length).toBeGreaterThan(0);
    expect(screen.getByTestId('header')).toBeInTheDocument();
    expect(screen.getByTestId('footer')).toBeInTheDocument();
  });
});
