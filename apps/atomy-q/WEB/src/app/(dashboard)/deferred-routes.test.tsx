import React from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { act, render, screen } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

vi.mock('@/lib/alpha-mode', async () => {
  const actual = await vi.importActual<typeof import('@/lib/alpha-mode')>('@/lib/alpha-mode');
  return {
    ...actual,
    isAlphaMode: () => true,
  };
});

vi.mock('@/hooks/use-rfq', () => ({
  useRfq: () => ({ data: { title: 'RFQ-1' }, isLoading: false }),
}));

import DocumentsPage from './documents/page';
import ReportingPage from './reporting/page';
import SettingsPage from './settings/page';
import SettingsUsersPage from './settings/users/page';
import NegotiationsPage from './rfqs/[rfqId]/negotiations/page';
import RfqDocumentsPage from './rfqs/[rfqId]/documents/page';
import RiskPage from './rfqs/[rfqId]/risk/page';

function renderWithQueryClient(ui: React.ReactElement) {
  const client = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });

  return render(<QueryClientProvider client={client}>{ui}</QueryClientProvider>);
}

async function renderDeferredRoute(ui: React.ReactElement) {
  await act(async () => {
    renderWithQueryClient(<React.Suspense fallback={null}>{ui}</React.Suspense>);
  });
}

describe('alpha deferred routes', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders the shared deferred screen for the hidden documents route', () => {
    renderWithQueryClient(<DocumentsPage />);
    expect(screen.getByText('This feature will be available in future releases')).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'Documents' })).toBeInTheDocument();
  });

  it('renders the live reporting shell when routed directly', () => {
    renderWithQueryClient(<ReportingPage />);
    expect(screen.getByRole('heading', { name: 'Reporting' })).toBeInTheDocument();
    expect(screen.getByText('Reports & analytics')).toBeInTheDocument();
  });

  it('renders the shared deferred screen for hidden settings page', () => {
    renderWithQueryClient(<SettingsPage />);
    expect(screen.getByRole('heading', { name: 'Settings' })).toBeInTheDocument();
    expect(screen.getByText('This feature will be available in future releases')).toBeInTheDocument();
  });

  it('renders the live settings users shell when routed directly', () => {
    renderWithQueryClient(<SettingsUsersPage />);
    expect(screen.getByRole('heading', { name: 'Users & Roles' })).toBeInTheDocument();
    expect(screen.getByText('Loading users and roles…')).toBeInTheDocument();
  });

  it('renders the shared deferred screen for hidden RFQ negotiations page', async () => {
    await renderDeferredRoute(<NegotiationsPage params={Promise.resolve({ rfqId: 'rfq-1' })} />);
    expect(screen.getByRole('heading', { name: 'Negotiations' })).toBeInTheDocument();
    expect(screen.getByText('This feature will be available in future releases')).toBeInTheDocument();
  });

  it('renders the shared deferred screen for hidden RFQ documents page', async () => {
    await renderDeferredRoute(<RfqDocumentsPage params={Promise.resolve({ rfqId: 'rfq-1' })} />);
    expect(screen.getByText('This feature will be available in future releases')).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'RFQ Documents' })).toBeInTheDocument();
  });

  it('renders the live RFQ risk page when routed directly', async () => {
    await renderDeferredRoute(<RiskPage params={Promise.resolve({ rfqId: 'rfq-1' })} />);
    expect(screen.getByRole('heading', { name: 'Risk & Compliance' })).toBeInTheDocument();
    expect(screen.getByText('Review vendor evidence and findings')).toBeInTheDocument();
  });
});
