import React from 'react';
import { beforeAll, describe, expect, it, vi, beforeEach } from 'vitest';
import { act, fireEvent, screen } from '@testing-library/react';
import { renderWithProviders } from '@/test/utils';

import { useAward } from '@/hooks/use-award';
import { useComparisonRun } from '@/hooks/use-comparison-run';
import { useComparisonRunMatrix } from '@/hooks/use-comparison-run-matrix';
import { useComparisonRuns } from '@/hooks/use-comparison-runs';
import { useAwardDebriefDraft } from '@/hooks/use-award-debrief-draft';
import { useAwardGuidance } from '@/hooks/use-award-guidance';
import { useRfqVendors } from '@/hooks/use-rfq-vendors';

beforeAll(() => {
  process.env.NEXT_PUBLIC_USE_MOCKS = 'false';
});

vi.mock('next/navigation', () => ({
  useRouter: () => ({ push: vi.fn() }),
}));

vi.mock('@/hooks/use-rfq', () => ({
  useRfq: () => ({ data: { title: 'RFQ', rfq_number: 'RFQ-1' } }),
}));

let aiStatusData = {
  isFeatureAvailable: () => true,
  shouldHideAiControls: () => false,
  shouldShowUnavailableMessage: () => false,
  messageKeyForFeature: () => null,
};

vi.mock('@/hooks/use-ai-status', () => ({
  useAiStatus: () => aiStatusData,
}));

vi.mock('@/hooks/use-award', async () => {
  const actual = await vi.importActual<typeof import('@/hooks/use-award')>('@/hooks/use-award');
  return {
    ...actual,
    useAward: vi.fn(),
  };
});
vi.mock('@/hooks/use-comparison-run');
vi.mock('@/hooks/use-comparison-run-matrix');
vi.mock('@/hooks/use-comparison-runs');
vi.mock('@/hooks/use-award-debrief-draft');
vi.mock('@/hooks/use-award-guidance');
vi.mock('@/hooks/use-rfq-vendors');

const mockAward = {
  id: 'award-1',
  rfq_id: 'rfq-1',
  rfq_title: 'RFQ',
  rfq_number: 'RFQ-1',
  comparison_run_id: 'run-1',
  vendor_id: 'vendor-1',
  vendor_name: 'Winner Vendor',
  status: 'pending',
  amount: 1000.00,
  currency: 'USD',
  split_details: [],
  protest_id: null,
  signoff_at: null,
  signed_off_by: null,
  comparison: {
    vendors: [
      { vendor_id: 'vendor-1', vendor_name: 'Winner Vendor', quote_submission_id: 'quote-1' },
      { vendor_id: 'vendor-2', vendor_name: 'Other Vendor', quote_submission_id: 'quote-2' },
    ],
  },
};

import { RfqAwardPageContent } from './page';

type UseAwardReturn = ReturnType<typeof useAward>;
type UseComparisonRunReturn = ReturnType<typeof useComparisonRun>;
type UseComparisonRunMatrixReturn = ReturnType<typeof useComparisonRunMatrix>;
type UseComparisonRunsReturn = ReturnType<typeof useComparisonRuns>;
type UseAwardDebriefDraftReturn = ReturnType<typeof useAwardDebriefDraft>;
type UseAwardGuidanceReturn = ReturnType<typeof useAwardGuidance>;
type UseRfqVendorsReturn = ReturnType<typeof useRfqVendors>;

describe('RfqAwardPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    
    aiStatusData = {
      isFeatureAvailable: () => true,
      shouldHideAiControls: () => false,
      shouldShowUnavailableMessage: () => false,
      messageKeyForFeature: () => null,
    };
    
    vi.mocked(useAward).mockReturnValue({
      award: mockAward,
      awards: [mockAward],
      signoff: { mutate: vi.fn(), isPending: false, isError: false },
      debrief: { mutate: vi.fn(), isPending: false, isError: false },
      store: { mutate: vi.fn(), isPending: false, isError: false },
    } as unknown as UseAwardReturn);

    vi.mocked(useComparisonRuns).mockReturnValue({
      data: [{ id: 'run-1', type: 'final', status: 'frozen' }],
    } as unknown as UseComparisonRunsReturn);

    vi.mocked(useComparisonRun).mockReturnValue({
      data: {
        id: 'run-1',
        rfqId: 'rfq-1',
        name: 'Final comparison',
        status: 'frozen',
        isPreview: false,
        createdAt: null,
        snapshot: {
          rfqVersion: 1,
          normalizedLines: [
            {
              rfqLineItemId: 'line-1',
              sourceDescription: 'Line 1',
              sourceLineId: null,
              quoteSubmissionId: null,
              vendorId: null,
              sourceUnitPrice: null,
              sourceUom: null,
              sourceQuantity: null,
            },
          ],
          resolutions: [],
          currencyMeta: { 'line-1': 'USD' },
          vendors: [
            { vendorId: 'vendor-1', vendorName: 'Winner Vendor', quoteSubmissionId: 'quote-1' },
            { vendorId: 'vendor-2', vendorName: 'Other Vendor', quoteSubmissionId: 'quote-2' },
          ],
        },
      },
    } as unknown as UseComparisonRunReturn);

    vi.mocked(useComparisonRunMatrix).mockReturnValue({
      data: {
        id: 'run-1',
        clusters: [
          {
            clusterKey: 'cluster-1',
            basis: 'price',
            offers: [
              {
                vendorId: 'vendor-1',
                rfqLineId: 'line-1',
                taxonomyCode: 'CAT-1',
                normalizedUnitPrice: 100,
                normalizedQuantity: 10,
                aiConfidence: 0.9,
              },
            ],
            statistics: {
              minNormalizedUnitPrice: 100,
              maxNormalizedUnitPrice: 100,
              avgNormalizedUnitPrice: 100,
            },
            recommendation: {
              recommendedVendorId: 'vendor-1',
              reason: 'lowest total',
            },
          },
        ],
      },
    } as unknown as UseComparisonRunMatrixReturn);

    vi.mocked(useAwardGuidance).mockReturnValue({
      data: {
        featureKey: 'award_ai_guidance',
        available: true,
        payload: {
          headline: 'Proceed with the top-ranked vendor.',
        },
        provenance: {
          provider: 'openrouter',
          endpoint_group: 'comparison_award',
        },
      },
      isLoading: false,
      isError: false,
      error: null,
    } as unknown as UseAwardGuidanceReturn);

    vi.mocked(useAwardDebriefDraft).mockReturnValue({
      data: {
        featureKey: 'award_ai_guidance',
        available: true,
        payload: {
          draft_message: 'Thank you for your proposal.',
        },
        provenance: {
          provider: 'openrouter',
          endpoint_group: 'comparison_award',
        },
      },
      isLoading: false,
      isError: false,
      error: null,
    } as unknown as UseAwardDebriefDraftReturn);

    vi.mocked(useRfqVendors).mockReturnValue({
      data: [
        { id: 'inv-1', vendor_id: 'vendor-1', name: 'Winner Vendor', status: 'responded' },
        { id: 'inv-2', vendor_id: 'vendor-2', name: 'Other Vendor', status: 'responded' },
      ],
    } as unknown as UseRfqVendorsReturn);
  });

  it('renders live award data and action buttons', async () => {
    renderWithProviders(<RfqAwardPageContent rfqId="rfq-1" />);

    expect(await screen.findByText('Winner Vendor', { selector: 'p' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /finalize award/i })).toBeEnabled();
    expect(screen.getByText('AI guidance')).toBeInTheDocument();
    expect(screen.getByText(/Proceed with the top-ranked vendor/i)).toBeInTheDocument();
    expect(screen.getAllByText(/openrouter/i).length).toBeGreaterThan(0);
  });

  it('renders existing award data when comparison runs fail to load', async () => {
    vi.mocked(useComparisonRuns).mockReturnValue({
      data: [],
      error: new Error('Comparison run snapshot missing'),
      isError: true,
    } as unknown as UseComparisonRunsReturn);

    renderWithProviders(<RfqAwardPageContent rfqId="rfq-1" />);

    expect(await screen.findByText('Winner Vendor', { selector: 'p' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /finalize award/i })).toBeEnabled();
    expect(screen.queryByText('Comparison run snapshot missing')).not.toBeInTheDocument();
  });

  it('allows sending debrief messages', async () => {
    renderWithProviders(<RfqAwardPageContent rfqId="rfq-1" />);

    const debriefInput = await screen.findByLabelText(/debrief message/i);
    const sendButton = screen.getByRole('button', { name: /send debrief/i }); // Corrected regex

    expect(sendButton).toBeDisabled();

    fireEvent.change(debriefInput, { target: { value: 'Thanks for your proposal.' } });

    expect(sendButton).toBeEnabled();
  });

  it('can apply an AI debrief draft into the editable textarea', async () => {
    renderWithProviders(<RfqAwardPageContent rfqId="rfq-1" />);

    const reviewDraftButtons = await screen.findAllByRole('button', { name: /review ai draft/i });
    fireEvent.click(reviewDraftButtons[0]);

    expect(screen.getByText(/thank you for your proposal/i)).toBeInTheDocument();

    fireEvent.click(screen.getByRole('button', { name: /apply ai draft/i }));

    expect((screen.getByLabelText(/debrief message/i) as HTMLTextAreaElement).value).toBe('Thank you for your proposal.');
  });

  it('shows award creation UI when no award exists', async () => {
    const storeMutate = vi.fn();
    vi.mocked(useAward).mockReturnValue({
      award: null,
      awards: [],
      signoff: { mutate: vi.fn(), isPending: false, isError: false },
      debrief: { mutate: vi.fn(), isPending: false, isError: false },
      store: { mutate: storeMutate, isPending: false, isError: false },
    } as unknown as UseAwardReturn);
    
    vi.mocked(useComparisonRuns).mockReturnValue({
      data: [{ id: 'run-1', type: 'final', status: 'frozen' }],
    } as unknown as UseComparisonRunsReturn);

    renderWithProviders(<RfqAwardPageContent rfqId="rfq-1" />);

    expect(await screen.findByText(/Select a vendor to award the contract based on the final comparison run/i)).toBeInTheDocument();
    expect(screen.getByRole('option', { name: 'Winner Vendor' })).toBeInTheDocument();
    expect(screen.queryByRole('option', { name: 'Other Vendor' })).not.toBeInTheDocument();
    fireEvent.click(screen.getByRole('button', { name: /create award/i }));

    expect(storeMutate).toHaveBeenCalledWith(
      { rfqId: 'rfq-1', comparisonRunId: 'run-1', vendorId: 'vendor-1' },
      expect.objectContaining({
        onError: expect.any(Function),
        onSuccess: expect.any(Function),
      }),
    );
    const callbacks = storeMutate.mock.calls[0]?.[1] as { onError: (error: Error) => void; onSuccess: () => void };

    act(() => {
      callbacks.onError(new Error('Award creation rejected'));
    });
    expect(screen.getByText('Award creation rejected')).toBeInTheDocument();

    act(() => {
      callbacks.onSuccess();
    });
    expect(screen.queryByText('Award creation rejected')).not.toBeInTheDocument();
  });

  it('shows a signoff error when finalization fails', async () => {
    const signoffMutate = vi.fn();

    vi.mocked(useAward).mockReturnValue({
      award: mockAward,
      awards: [mockAward],
      signoff: { mutate: signoffMutate, isPending: false, isError: false },
      debrief: { mutate: vi.fn(), isPending: false, isError: false },
      store: { mutate: vi.fn(), isPending: false, isError: false },
    } as unknown as UseAwardReturn);

    renderWithProviders(<RfqAwardPageContent rfqId="rfq-1" />);

    fireEvent.click(await screen.findByRole('button', { name: /finalize award/i }));

    const callbacks = signoffMutate.mock.calls[0]?.[1] as { onError: (error: Error) => void };
    act(() => {
      callbacks.onError(new Error('Award signoff rejected'));
    });

    expect(screen.getByText('Award signoff rejected')).toBeInTheDocument();
  });

  it('renders an explicit error path when award data fails to load', async () => {
    vi.mocked(useAward).mockReturnValue({
      award: null,
      awards: [],
      error: new Error('Award payload rejected'),
      isError: true,
      signoff: { mutate: vi.fn(), isPending: false, isError: false },
      debrief: { mutate: vi.fn(), isPending: false, isError: false },
      store: { mutate: vi.fn(), isPending: false, isError: false },
    } as unknown as UseAwardReturn);

    renderWithProviders(<RfqAwardPageContent rfqId="rfq-1" />);

    expect(await screen.findByText('Award payload rejected')).toBeInTheDocument();
    expect(screen.queryByText('No award record yet')).not.toBeInTheDocument();
    expect(screen.queryByText('Freeze a comparison run to create an award record.')).not.toBeInTheDocument();
  });

  it('renders an explicit error path when comparison runs fail to load and no award exists', async () => {
    vi.mocked(useAward).mockReturnValue({
      award: null,
      awards: [],
      signoff: { mutate: vi.fn(), isPending: false, isError: false },
      debrief: { mutate: vi.fn(), isPending: false, isError: false },
      store: { mutate: vi.fn(), isPending: false, isError: false },
    } as unknown as UseAwardReturn);

    vi.mocked(useComparisonRuns).mockReturnValue({
      data: [],
      error: new Error('Comparison run snapshot missing'),
      isError: true,
    } as unknown as UseComparisonRunsReturn);

    renderWithProviders(<RfqAwardPageContent rfqId="rfq-1" />);

    expect(await screen.findByText('Comparison run snapshot missing')).toBeInTheDocument();
    expect(screen.queryByText('No award record yet')).not.toBeInTheDocument();
    expect(screen.queryByText('Freeze a comparison run to create an award record.')).not.toBeInTheDocument();
  });

  it('hides vendors without complete finalized pricing coverage from the create-award selection', async () => {
    const storeMutate = vi.fn();

    vi.mocked(useAward).mockReturnValue({
      award: null,
      awards: [],
      signoff: { mutate: vi.fn(), isPending: false, isError: false },
      debrief: { mutate: vi.fn(), isPending: false, isError: false },
      store: { mutate: storeMutate, isPending: false, isError: false },
    } as unknown as UseAwardReturn);

    vi.mocked(useComparisonRun).mockReturnValue({
      data: {
        id: 'run-1',
        rfqId: 'rfq-1',
        name: 'Final comparison',
        status: 'frozen',
        isPreview: false,
        createdAt: null,
        snapshot: {
          rfqVersion: 1,
          normalizedLines: [
            {
              rfqLineItemId: 'line-1',
              sourceDescription: 'Line 1',
              sourceLineId: null,
              quoteSubmissionId: null,
              vendorId: null,
              sourceUnitPrice: null,
              sourceUom: null,
              sourceQuantity: null,
            },
            {
              rfqLineItemId: 'line-2',
              sourceDescription: 'Line 2',
              sourceLineId: null,
              quoteSubmissionId: null,
              vendorId: null,
              sourceUnitPrice: null,
              sourceUom: null,
              sourceQuantity: null,
            },
          ],
          resolutions: [],
          currencyMeta: { 'line-1': 'USD', 'line-2': 'USD' },
          vendors: [
            { vendorId: 'vendor-1', vendorName: 'Winner Vendor', quoteSubmissionId: 'quote-1' },
            { vendorId: 'vendor-2', vendorName: 'Other Vendor', quoteSubmissionId: 'quote-2' },
          ],
        },
      },
    } as unknown as UseComparisonRunReturn);

    vi.mocked(useComparisonRunMatrix).mockReturnValue({
      data: {
        id: 'run-1',
        clusters: [
          {
            clusterKey: 'cluster-1',
            basis: 'price',
            offers: [
              {
                vendorId: 'vendor-1',
                rfqLineId: 'line-1',
                taxonomyCode: 'CAT-1',
                normalizedUnitPrice: 100,
                normalizedQuantity: 10,
                aiConfidence: 0.9,
              },
              {
                vendorId: 'vendor-1',
                rfqLineId: 'line-2',
                taxonomyCode: 'CAT-2',
                normalizedUnitPrice: 50,
                normalizedQuantity: 5,
                aiConfidence: 0.9,
              },
              {
                vendorId: 'vendor-2',
                rfqLineId: 'line-1',
                taxonomyCode: 'CAT-1',
                normalizedUnitPrice: 90,
                normalizedQuantity: 10,
                aiConfidence: 0.9,
              },
            ],
            statistics: {
              minNormalizedUnitPrice: 50,
              maxNormalizedUnitPrice: 100,
              avgNormalizedUnitPrice: 80,
            },
            recommendation: {
              recommendedVendorId: 'vendor-1',
              reason: 'lowest complete total',
            },
          },
        ],
      },
    } as unknown as UseComparisonRunMatrixReturn);

    renderWithProviders(<RfqAwardPageContent rfqId="rfq-1" />);

    expect(await screen.findByRole('option', { name: 'Winner Vendor' })).toBeInTheDocument();
    expect(screen.queryByRole('option', { name: 'Other Vendor' })).not.toBeInTheDocument();
  });

  it('renders the award AI guidance unavailable state while keeping manual actions available', async () => {
    vi.mocked(useAwardGuidance).mockReturnValue({
      data: {
        featureKey: 'award_ai_guidance',
        available: false,
        payload: null,
        provenance: {
          source: 'deterministic',
        },
      },
      isLoading: false,
      isError: false,
      error: null,
    } as unknown as UseAwardGuidanceReturn);

    renderWithProviders(<RfqAwardPageContent rfqId="rfq-1" />);

    expect(await screen.findByText('Award AI guidance unavailable')).toBeInTheDocument();
    expect(screen.queryByText('Award payload rejected')).not.toBeInTheDocument();
    expect(screen.getByRole('button', { name: /finalize award/i })).toBeEnabled();
  });
});
