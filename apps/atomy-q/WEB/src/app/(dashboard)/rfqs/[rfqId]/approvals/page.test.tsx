import React from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { fireEvent, screen, waitFor, within } from '@testing-library/react';
import { normalizeApprovalSummary } from '@/hooks/use-approval-summary';
import { renderWithProviders } from '@/test/utils';

let aiStatusData = {
  isFeatureAvailable: () => true,
  shouldHideAiControls: () => false,
  shouldShowUnavailableMessage: () => false,
  messageKeyForFeature: () => null,
};

const approvalRows = [
  {
    id: 'approval-1',
    rfq_id: 'rfq-1',
    type: 'Compliance review',
    summary: 'Review contract exceptions.',
    priority: 'high',
    assignee: 'Morgan',
  },
  {
    id: 'approval-2',
    rfq_id: 'rfq-1',
    type: 'Finance review',
    summary: 'Confirm pricing delta is within threshold.',
    priority: 'medium',
    assignee: 'Riley',
  },
];

const approvalSummaryById: Record<string, { data: { ai_summary: Record<string, unknown> } }> = {
  'approval-1': {
    data: {
      ai_summary: {
        feature_key: 'approval_ai_summary',
        available: true,
        payload: {
          headline: 'Approval can proceed with the frozen comparison evidence.',
          rationale: ['comparison_run_final', 'policy_thresholds_met'],
          provenance: {
            provider: 'openrouter',
            endpoint_group: 'comparison_award',
          },
        },
        provenance: {
          provider: 'openrouter',
          endpoint_group: 'comparison_award',
        },
      },
    },
  },
  'approval-2': {
    data: {
      ai_summary: {
        feature_key: 'approval_ai_summary',
        available: true,
        payload: {
          headline: 'Finance can clear this review after checking the variance note.',
        },
        provenance: {
          provider: 'openrouter',
          endpoint_group: 'comparison_award',
        },
      },
    },
  },
};

const mockUseApprovalSummary = vi.fn();

vi.mock('next/navigation', () => ({
  useRouter: () => ({ push: vi.fn() }),
}));

vi.mock('@/hooks/use-rfq', () => ({
  useRfq: () => ({ data: { title: 'RFQ', rfq_number: 'RFQ-1' } }),
}));

vi.mock('@/hooks/use-ai-status', () => ({
  useAiStatus: () => aiStatusData,
}));

vi.mock('@/hooks/use-approval-summary', async () => {
  const actual = await vi.importActual<typeof import('@/hooks/use-approval-summary')>('@/hooks/use-approval-summary');
  return {
    ...actual,
    useApprovalSummary: (...args: unknown[]) => mockUseApprovalSummary(...args),
  };
});

vi.mock('@/hooks/use-approvals', () => ({
  useApprovalsList: () => ({
    data: {
      items: approvalRows,
    },
  }),
}));

import ApprovalsListPage from './page';

describe('ApprovalsListPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    aiStatusData = {
      isFeatureAvailable: () => true,
      shouldHideAiControls: () => false,
      shouldShowUnavailableMessage: () => false,
      messageKeyForFeature: () => null,
    };
    mockUseApprovalSummary.mockImplementation((approvalId: string) => {
      const summary = approvalSummaryById[approvalId];
      return summary
        ? {
            data: normalizeApprovalSummary(summary),
            isLoading: false,
            isError: false,
            error: null,
          }
        : {
            data: null,
            isLoading: false,
            isError: true,
            error: new Error('Approval AI summary unavailable'),
          };
    });
  });

  it('renders the AI summary aid panel and the selected approval summary payload', async () => {
    renderWithProviders(<ApprovalsListPage params={Promise.resolve({ rfqId: 'rfq-1' })} />);

    expect(await screen.findByRole('heading', { name: 'AI summary aid' })).toBeInTheDocument();
    expect(screen.getAllByText('approval-1').length).toBeGreaterThan(0);
    expect(screen.getAllByText(/approval can proceed with the frozen comparison evidence/i).length).toBeGreaterThan(0);
    expect(screen.getByText(/view raw provider payload/i)).toBeInTheDocument();
    expect(screen.getAllByText(/openrouter/i).length).toBeGreaterThan(0);
    expect(screen.getAllByText('Compliance review').length).toBeGreaterThan(0);
    expect(mockUseApprovalSummary).toHaveBeenCalledWith('approval-1', expect.objectContaining({ enabled: true }));
  });

  it('reveals raw payload and provenance only after toggling view raw', async () => {
    renderWithProviders(<ApprovalsListPage params={Promise.resolve({ rfqId: 'rfq-1' })} />);

    fireEvent.click(await screen.findByRole('button', { name: /view raw provider payload/i }));
    fireEvent.click(screen.getByRole('button', { name: /view raw provenance/i }));

    expect(screen.getAllByText(/openrouter/i).length).toBeGreaterThan(0);
    expect(screen.getAllByText(/comparison_award/i).length).toBeGreaterThan(0);
  });

  it('updates the summary when a different approval row is selected', async () => {
    renderWithProviders(<ApprovalsListPage params={Promise.resolve({ rfqId: 'rfq-1' })} />);

    expect((await screen.findAllByText(/approval can proceed with the frozen comparison evidence/i)).length).toBeGreaterThan(0);

    const financeCell = screen.getByText('Finance review');
    const financeRow = financeCell.closest('tr');
    expect(financeRow).not.toBeNull();
    fireEvent.click(within(financeRow as HTMLTableRowElement).getByRole('checkbox'));

    await waitFor(() => expect(screen.getAllByText(/finance can clear this review/i).length).toBeGreaterThan(0));
    expect(mockUseApprovalSummary).toHaveBeenCalledWith('approval-2', expect.objectContaining({ enabled: true }));
  });

  it('renders the AI summary aid unavailable state without blocking the list', async () => {
    mockUseApprovalSummary.mockReturnValue({
      data: {
        featureKey: 'approval_ai_summary',
        available: false,
        payload: null,
        provenance: {
          source: 'deterministic',
        },
      },
      isLoading: false,
      isError: false,
      error: null,
    });

    renderWithProviders(<ApprovalsListPage params={Promise.resolve({ rfqId: 'rfq-1' })} />);

    expect(await screen.findByRole('heading', { name: 'AI summary aid' })).toBeInTheDocument();
    expect(screen.getByText('Approval AI summary unavailable')).toBeInTheDocument();
    expect(screen.getByText('approval-1')).toBeInTheDocument();
  });
});
