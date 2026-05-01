import React from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

import { renderPageWithProviders } from '@/test/utils';

const mockUseVendorGovernance = vi.fn();
const mockUseGenerateVendorGovernanceNarrative = vi.fn();
const mockGenerateGovernance = vi.fn();

vi.mock('@/hooks/use-ai-status', () => ({
  useAiStatus: () => ({
    isFeatureAvailable: () => true,
    shouldHideAiControls: () => false,
    shouldShowUnavailableMessage: () => false,
    messageKeyForFeature: () => null,
  }),
}));

vi.mock('@/hooks/use-vendor-governance', async () => {
  const actual = await vi.importActual<typeof import('@/hooks/use-vendor-governance')>('@/hooks/use-vendor-governance');
  return {
    ...actual,
    useVendorGovernance: (...args: unknown[]) => mockUseVendorGovernance(...args),
    useGenerateVendorGovernanceNarrative: (...args: unknown[]) => mockUseGenerateVendorGovernanceNarrative(...args),
  };
});

import VendorEsgCompliancePage from './page';

describe('VendorEsgCompliancePage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockUseGenerateVendorGovernanceNarrative.mockReturnValue({
      mutate: mockGenerateGovernance,
      isPending: false,
    });
  });

  it('renders scores, evidence, findings, and warning flags', async () => {
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

    await renderPageWithProviders(<VendorEsgCompliancePage params={Promise.resolve({ vendorId: 'vendor-1' })} />);

    expect(await screen.findByText('ESG / Compliance')).toBeInTheDocument();
    expect(screen.getByText('Compliance Document Expired')).toBeInTheDocument();
    expect(screen.getByText('Open Severe Risk Finding')).toBeInTheDocument();
    expect(screen.getByText('ESG Review Stale')).toBeInTheDocument();
    expect(screen.getByText('ISO 14001 certificate')).toBeInTheDocument();
    expect(screen.getByText('Observed Jan 1, 2025 · Expires Jan 1, 2026')).toBeInTheDocument();
    expect(screen.getByText('financial_watch')).toBeInTheDocument();
    expect(screen.getByText('55')).toBeInTheDocument();
    expect(screen.getAllByText('45')).toHaveLength(2);
  });

  it('passes the live vendor id when generating governance narrative', async () => {
    const user = userEvent.setup();
    mockUseVendorGovernance.mockReturnValue({
      data: {
        vendorId: 'vendor-1',
        evidence: [],
        findings: [],
        scores: {
          esgScore: 55,
          complianceHealthScore: 45,
          riskWatchScore: 45,
          evidenceFreshnessScore: 25,
        },
        warningFlags: [],
        narrative: null,
      },
      isLoading: false,
      isError: false,
      error: null,
    });

    await renderPageWithProviders(<VendorEsgCompliancePage params={Promise.resolve({ vendorId: 'vendor-1' })} />);

    await user.click(await screen.findByRole('button', { name: 'Generate' }));

    expect(mockGenerateGovernance).toHaveBeenCalledWith('vendor-1');
  });

  it('surfaces an explicit unavailable live payload state', async () => {
    mockUseVendorGovernance.mockReturnValue({
      data: undefined,
      isLoading: false,
      isError: true,
      error: new Error('Invalid vendor governance payload'),
    });

    await renderPageWithProviders(<VendorEsgCompliancePage params={Promise.resolve({ vendorId: 'vendor-1' })} />);

    expect(await screen.findByText(/could not load governance data/i)).toBeInTheDocument();
    expect(screen.getByText(/invalid vendor governance payload/i)).toBeInTheDocument();
  });
});
