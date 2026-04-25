import React from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';

import { renderWithProviders } from '@/test/utils';

const mockUseVendorGovernance = vi.fn();

vi.mock('@/hooks/use-vendor-governance', async () => {
  const actual = await vi.importActual<typeof import('@/hooks/use-vendor-governance')>('@/hooks/use-vendor-governance');
  return {
    ...actual,
    useVendorGovernance: (...args: unknown[]) => mockUseVendorGovernance(...args),
  };
});

import VendorEsgCompliancePage from './page';

describe('VendorEsgCompliancePage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders scores, evidence, findings, and warning flags', () => {
    mockUseVendorGovernance.mockReturnValue({
      data: {
        vendorId: 'vendor-1',
        evidence: [
          {
            id: 'ev-1',
            vendorId: 'vendor-1',
            domain: 'compliance',
            type: 'iso_certificate',
            title: 'ISO 14001 certificate',
            source: 'manual',
            observedAt: '2025-01-01T00:00:00Z',
            expiresAt: '2026-01-01T00:00:00Z',
            reviewStatus: 'pending',
            reviewedBy: null,
            notes: null,
          },
        ],
        findings: [
          {
            id: 'finding-1',
            vendorId: 'vendor-1',
            domain: 'risk',
            issueType: 'financial_watch',
            severity: 'high',
            status: 'open',
            openedAt: '2026-04-01T00:00:00Z',
            openedBy: 'risk-user',
            remediationOwner: 'procurement',
            remediationDueAt: '2026-04-15T00:00:00Z',
            resolutionSummary: null,
          },
        ],
        scores: {
          esgScore: 55,
          complianceHealthScore: 45,
          riskWatchScore: 45,
          evidenceFreshnessScore: 25,
        },
        warningFlags: ['compliance_document_expired', 'open_severe_risk_finding', 'esg_review_stale'],
      },
      isLoading: false,
      isError: false,
      error: null,
    });

    renderWithProviders(<VendorEsgCompliancePage params={Promise.resolve({ vendorId: 'vendor-1' })} />);

    expect(screen.getByText('ESG / Compliance')).toBeInTheDocument();
    expect(screen.getByText('Compliance Document Expired')).toBeInTheDocument();
    expect(screen.getByText('Open Severe Risk Finding')).toBeInTheDocument();
    expect(screen.getByText('ESG Review Stale')).toBeInTheDocument();
    expect(screen.getByText('ISO 14001 certificate')).toBeInTheDocument();
    expect(screen.getByText('Observed Jan 1, 2025 · Expires Jan 1, 2026')).toBeInTheDocument();
    expect(screen.getByText('financial_watch')).toBeInTheDocument();
    expect(screen.getByText('55')).toBeInTheDocument();
    expect(screen.getAllByText('45')).toHaveLength(2);
  });

  it('surfaces an explicit unavailable live payload state', () => {
    mockUseVendorGovernance.mockReturnValue({
      data: undefined,
      isLoading: false,
      isError: true,
      error: new Error('Invalid vendor governance payload'),
    });

    renderWithProviders(<VendorEsgCompliancePage params={Promise.resolve({ vendorId: 'vendor-1' })} />);

    expect(screen.getByText(/could not load governance data/i)).toBeInTheDocument();
    expect(screen.getByText(/invalid vendor governance payload/i)).toBeInTheDocument();
  });
});
