import React, { Suspense } from 'react';
import { beforeAll, beforeEach, describe, expect, it, vi } from 'vitest';
import { act, fireEvent, screen } from '@testing-library/react';
import { renderWithProviders } from '@/test/utils';

const mockUseNormalizationReview = vi.fn();
const mockUseNormalizationSourceLines = vi.fn();
const mockUseComparisonReadiness = vi.fn();
const mockCreateSourceLine = vi.fn();
const mockOverrideSourceLine = vi.fn();
const mockDeleteSourceLine = vi.fn();
const mockUseAiStatus = vi.fn();

beforeAll(() => {
  process.env.NEXT_PUBLIC_USE_MOCKS = 'false';
});

vi.mock('@/hooks/use-normalization-review', async (importOriginal) => {
  const actual = await importOriginal<typeof import('@/hooks/use-normalization-review')>();
  return {
    ...actual,
    useNormalizationReview: (...args: unknown[]) => mockUseNormalizationReview(...args),
  };
});

vi.mock('@/hooks/use-normalization-source-lines', () => ({
  useNormalizationSourceLines: (...args: unknown[]) => mockUseNormalizationSourceLines(...args),
  useManualNormalizationSourceLineMutations: () => ({
    createSourceLine: { mutate: mockCreateSourceLine, isPending: false, isError: false, error: null },
    overrideSourceLine: { mutate: mockOverrideSourceLine, isPending: false, isError: false, error: null },
    deleteSourceLine: { mutate: mockDeleteSourceLine, isPending: false, isError: false, error: null },
  }),
}));

vi.mock('@/hooks/use-comparison-readiness', () => ({
  useComparisonReadiness: (...args: unknown[]) => mockUseComparisonReadiness(...args),
}));

vi.mock('@/hooks/use-freeze-comparison', () => ({
  useFreezeComparison: () => ({
    mutate: vi.fn(),
    isPending: false,
    isError: false,
  }),
}));

vi.mock('@/hooks/use-rfq', () => ({
  useRfq: vi.fn(() => ({ data: { title: 'RFQ', submission_deadline: '2026-04-01T00:00:00.000Z' } })),
}));

vi.mock('@/hooks/use-rfq-line-items', () => ({
  useRfqLineItems: () => ({
    data: [
      {
        id: 'rfq-line-1',
        rfq_id: 'r1',
        description: 'RFQ Widget A',
        quantity: 2,
        uom: 'ea',
        unit_price: 10,
        currency: 'USD',
        specifications: null,
        sort_order: 1,
      },
      {
        id: 'rfq-line-2',
        rfq_id: 'r1',
        description: 'RFQ Widget B',
        quantity: 4,
        uom: 'ea',
        unit_price: 42,
        currency: 'USD',
        specifications: null,
        sort_order: 2,
      },
    ],
  }),
}));

vi.mock('@/hooks/use-ai-status', () => ({
  useAiStatus: () => mockUseAiStatus(),
}));

import NormalizePage from './page';
import { useRfq } from '@/hooks/use-rfq';

async function renderPage() {
  await act(async () => {
    renderWithProviders(
      <Suspense fallback={null}>
        <NormalizePage params={Promise.resolve({ rfqId: 'r1', quoteId: 'q1' })} />
      </Suspense>,
    );
  });
}

describe('NormalizeQuotePage', () => {
  beforeEach(() => {
    vi.clearAllMocks();

    mockUseNormalizationReview.mockReturnValue({
      conflicts: [],
      hasBlockingIssues: true,
      blockingIssueCount: 2,
      isLoading: false,
      isError: false,
      error: null,
      data: undefined,
      status: 'success',
      fetchStatus: 'idle',
      resolveConflict: { mutate: vi.fn(), isPending: false, isError: false },
    });

    mockUseComparisonReadiness.mockReturnValue({
      allQuotesReady: true,
      canFreezeComparison: true,
      hasBlockingIssues: false,
      blockingIssueCount: 0,
      overview: { data: { normalization: { total_quotes: 2, ready_count: 2, accepted_count: 2 } } },
      normalization: {},
    });

    mockUseNormalizationSourceLines.mockReturnValue({
      data: [
        {
          id: 'line-1',
          quote_submission_id: 'q1',
          vendor_id: 'vendor-1',
          vendor_name: 'Vendor 1',
          source_description: 'Widget A',
          source_quantity: '2',
          source_uom: 'ea',
          source_unit_price: '',
          rfq_line_item_id: 'rfq-line-1',
          rfq_line_description: 'RFQ Widget A',
          rfq_line_quantity: '2',
          rfq_line_uom: 'ea',
          rfq_line_unit_price: '',
          sort_order: 0,
          confidence: 'high',
          conflict_count: 0,
          blocking_issue_count: 0,
          has_blocking_issue: false,
          quote_submission_status: 'ready',
          ai_confidence: '87.50',
          provider_suggested: {
            rfq_line_item_id: 'rfq-line-2',
            quantity: '2.0000',
            uom: 'ea',
            unit_price: '12.5000',
          },
          effective_values: {
            rfq_line_item_id: 'rfq-line-1',
            quantity: '2.0000',
            uom: 'ea',
            unit_price: '10.0000',
          },
          is_buyer_overridden: true,
          latest_override: {
            reason_code: 'price_correction',
            note: 'Typed from signed quote',
            actor_name: 'Buyer One',
            overridden_at: '2026-04-26T01:00:00Z',
          },
        },
        {
          id: 'line-2',
          quote_submission_id: 'q1',
          vendor_id: 'vendor-2',
          vendor_name: 'Vendor 2',
          source_description: 'Widget B',
          source_quantity: '4',
          source_uom: 'ea',
          source_unit_price: '42',
          rfq_line_item_id: 'rfq-line-2',
          rfq_line_description: 'RFQ Widget B',
          rfq_line_quantity: '4',
          rfq_line_uom: 'ea',
          rfq_line_unit_price: '42',
          sort_order: 1,
          confidence: 'medium',
          conflict_count: 0,
          blocking_issue_count: 0,
          has_blocking_issue: false,
          quote_submission_status: 'ready',
          ai_confidence: '64.25',
          provider_suggested: {
            rfq_line_item_id: 'rfq-line-2',
            quantity: '4.0000',
            uom: 'ea',
            unit_price: '42.0000',
          },
          effective_values: {
            rfq_line_item_id: 'rfq-line-2',
            quantity: '4.0000',
            uom: 'ea',
            unit_price: '42.0000',
          },
          is_buyer_overridden: false,
          latest_override: null,
        },
      ],
      isLoading: false,
      isError: false,
      error: null,
    });

    mockUseAiStatus.mockReturnValue({
      isFeatureAvailable: (featureKey: string) =>
        featureKey === 'quote_document_extraction' || featureKey === 'normalization_suggestions',
      shouldHideAiControls: () => false,
      shouldShowUnavailableMessage: () => false,
      messageKeyForFeature: () => null,
      status: { mode: 'provider', globalHealth: 'healthy', providerName: 'OpenRouter' },
    });
  });

  it('shows blocking issues before allowing freeze', async () => {
    await renderPage();

    expect(await screen.findByText(/blocking issues/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /freeze comparison/i })).toBeDisabled();
  });

  it('keeps freeze disabled while the RFQ submission deadline is still open', async () => {
    vi.mocked(useRfq).mockReturnValueOnce({
      data: { title: 'RFQ', submission_deadline: '2099-04-01T00:00:00.000Z' },
    } as ReturnType<typeof useRfq>);

    mockUseNormalizationReview.mockReturnValue({
      conflicts: [],
      hasBlockingIssues: false,
      blockingIssueCount: 0,
      isLoading: false,
      isError: false,
      error: null,
      data: undefined,
      status: 'success',
      fetchStatus: 'idle',
      resolveConflict: { mutate: vi.fn(), isPending: false, isError: false },
    });

    await renderPage();

    expect(await screen.findByText(/comparison freeze unavailable/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /freeze comparison/i })).toBeDisabled();
  });

  it('does not coerce empty unit prices to zero', async () => {
    await renderPage();

    expect(await screen.findByText('Widget A')).toBeInTheDocument();
    expect(screen.getAllByText('$42').length).toBeGreaterThan(0);
    expect(screen.queryByText('$0')).not.toBeInTheDocument();
  });

  it('renders an explicit unavailable state when source lines fail to load in live mode', async () => {
    mockUseNormalizationSourceLines.mockReturnValue({
      data: [],
      isLoading: false,
      isError: true,
      error: new Error('Normalization source lines unavailable'),
    });

    await renderPage();

    expect(await screen.findByText(/source lines unavailable/i)).toBeInTheDocument();
    expect(screen.getByText(/normalization source lines unavailable/i)).toBeInTheDocument();
    expect(screen.getByRole('textbox', { name: /description/i })).toBeInTheDocument();
    expect(screen.queryByText(/normalize workspace unavailable/i)).not.toBeInTheDocument();
  });

  it('renders an explicit unavailable state when normalization review fails in live mode', async () => {
    mockUseNormalizationReview.mockReturnValue({
      conflicts: [],
      hasBlockingIssues: false,
      blockingIssueCount: 0,
      isLoading: false,
      isError: true,
      error: new Error('Normalization review unavailable'),
      data: undefined,
      status: 'error',
      fetchStatus: 'idle',
      resolveConflict: { mutate: vi.fn(), isPending: false, isError: false },
    });

    await renderPage();

    expect(await screen.findByText(/normalization review unavailable/i)).toBeInTheDocument();
    expect(screen.getByText(/normalization review unavailable/i)).toBeInTheDocument();
    expect(screen.getByText('Widget A')).toBeInTheDocument();
    expect(screen.queryByText(/normalize workspace unavailable/i)).not.toBeInTheDocument();
  });

  it('shows manual source-line entry controls when extraction is degraded', async () => {
    mockUseAiStatus.mockReturnValue({
      isFeatureAvailable: () => false,
      shouldHideAiControls: (featureKey: string) => featureKey === 'quote_document_extraction',
      shouldShowUnavailableMessage: (featureKey: string) => featureKey === 'quote_document_extraction',
      messageKeyForFeature: () => 'ai.status.degraded',
      status: { mode: 'provider', globalHealth: 'degraded', providerName: null },
    });

    await renderPage();

    expect(await screen.findByText(/ai extraction is unavailable/i)).toBeInTheDocument();
    expect(screen.getByRole('textbox', { name: /description/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /add source line/i })).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /manual assist/i })).not.toBeInTheDocument();
  });

  it('requires a structured reason code before manual source-line submit and note for other', async () => {
    await renderPage();

    fireEvent.change(await screen.findByRole('textbox', { name: /description/i }), {
      target: { value: 'Manual freight line' },
    });

    expect(screen.getByRole('button', { name: /add source line/i })).toBeDisabled();

    fireEvent.change(screen.getByLabelText(/reason code/i), {
      target: { value: 'other' },
    });
    expect(screen.getByRole('button', { name: /add source line/i })).toBeDisabled();

    fireEvent.change(screen.getByLabelText(/note/i), {
      target: { value: 'Needed because supplier file was unreadable' },
    });
    expect(screen.getByRole('button', { name: /add source line/i })).toBeEnabled();
  });

  it('renders provider confidence and buyer override state', async () => {
    await renderPage();

    expect(await screen.findByText(/buyer override/i)).toBeInTheDocument();
    expect(screen.getByText(/provider confidence 87\.50%/i)).toBeInTheDocument();
    expect(screen.getByText(/override reason price correction/i)).toBeInTheDocument();
  });

  it('submits manual source-line create, update, and delete actions', async () => {
    await renderPage();

    fireEvent.change(await screen.findByRole('textbox', { name: /description/i }), {
      target: { value: 'Manual freight line' },
    });
    fireEvent.change(screen.getByLabelText(/quantity/i), { target: { value: '1' } });
    fireEvent.change(screen.getByLabelText(/uom/i), { target: { value: 'lot' } });
    fireEvent.change(screen.getByLabelText(/unit price/i), { target: { value: '250' } });
    fireEvent.change(screen.getByLabelText(/rfq line/i), { target: { value: 'rfq-line-1' } });
    fireEvent.change(screen.getByLabelText(/note/i), { target: { value: 'Typed from vendor email' } });
    fireEvent.change(screen.getByLabelText(/reason code/i), { target: { value: 'manual_entry_required' } });
    fireEvent.click(screen.getByRole('button', { name: /add source line/i }));

    expect(mockCreateSourceLine).toHaveBeenCalledWith({
      quoteSubmissionId: 'q1',
      source_description: 'Manual freight line',
      source_quantity: '1',
      source_uom: 'lot',
      source_unit_price: '250',
      rfq_line_item_id: 'rfq-line-1',
      note: 'Typed from vendor email',
      reason: 'manual_entry_required',
    });

    fireEvent.click(screen.getByRole('button', { name: /edit source line 1/i }));
    fireEvent.change(screen.getByLabelText(/reason code source line 1/i), { target: { value: 'price_correction' } });
    fireEvent.click(screen.getByRole('button', { name: /save source line 1/i }));
    expect(mockOverrideSourceLine).toHaveBeenCalledWith({
      id: 'line-1',
      override_data: {
        rfq_line_item_id: 'rfq-line-1',
        quantity: '2',
        uom: 'ea',
        unit_price: null,
      },
      note: 'Typed from signed quote',
      reason_code: 'price_correction',
    });

    vi.spyOn(window, 'confirm').mockReturnValueOnce(true);
    fireEvent.click(screen.getByRole('button', { name: /delete source line 1/i }));
    expect(mockDeleteSourceLine).toHaveBeenCalledWith({ quoteSubmissionId: 'q1', id: 'line-1' });
  });
});
