import React from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { render, screen } from '@testing-library/react';

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

describe('alpha deferred routes', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it.each([
    { Component: DocumentsPage, heading: 'Documents' },
    { Component: ReportingPage, heading: 'Reporting' },
  ])('renders the shared deferred screen for hidden top-level routes', ({ Component, heading }) => {
    render(<Component />);
    expect(screen.getByText('This feature will be available in future releases')).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: heading })).toBeInTheDocument();
  });

  it('renders the shared deferred screen for hidden settings page', () => {
    render(<SettingsPage />);
    expect(screen.getByRole('heading', { name: 'Settings' })).toBeInTheDocument();
    expect(screen.getByText('This feature will be available in future releases')).toBeInTheDocument();
  });

  it('renders the shared deferred screen for hidden settings subpage', () => {
    render(<SettingsUsersPage />);
    expect(screen.getByRole('heading', { name: 'Users & Roles' })).toBeInTheDocument();
    expect(screen.getByText('This feature will be available in future releases')).toBeInTheDocument();
  });

  it('renders the shared deferred screen for hidden RFQ negotiations page', () => {
    render(<NegotiationsPage params={Promise.resolve({ rfqId: 'rfq-1' })} />);
    expect(screen.getByRole('heading', { name: 'Negotiations' })).toBeInTheDocument();
    expect(screen.getByText('This feature will be available in future releases')).toBeInTheDocument();
  });

  it('renders the shared deferred screen for hidden RFQ documents page', () => {
    render(<RfqDocumentsPage params={Promise.resolve({ rfqId: 'rfq-1' })} />);
    expect(screen.getByText('This feature will be available in future releases')).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'RFQ Documents' })).toBeInTheDocument();
  });

  it('renders the shared deferred screen for hidden RFQ risk page', () => {
    render(<RiskPage params={Promise.resolve({ rfqId: 'rfq-1' })} />);
    expect(screen.getByText('This feature will be available in future releases')).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'Risk & Compliance' })).toBeInTheDocument();
  });
});
