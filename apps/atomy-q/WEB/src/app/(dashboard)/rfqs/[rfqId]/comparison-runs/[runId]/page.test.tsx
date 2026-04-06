import React from 'react';
import { beforeAll, beforeEach, describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import { renderWithProviders } from '@/test/utils';

beforeAll(() => {
  process.env.NEXT_PUBLIC_USE_MOCKS = 'false';
});

const createComparisonRunData = (): Record<string, unknown> => ({
  id: 'run-42',
  rfqId: 'rfq-1',
  name: 'Final comparison',
  status: 'final',
  isPreview: false,
  createdAt: '2026-04-06T08:00:00Z',
  snapshot: {
    rfqVersion: 1712390400,
    resolutions: [],
    currencyMeta: { 'line-1': 'USD' },
    vendors: [
      { vendorId: 'vendor-1', vendorName: 'Vendor One', quoteSubmissionId: 'quote-1' },
      { vendorId: 'vendor-2', vendorName: 'Vendor Two', quoteSubmissionId: 'quote-2' },
    ],
    normalizedLines: [
      {
        sourceLineId: 'source-1',
        quoteSubmissionId: 'quote-1',
        vendorId: 'vendor-1',
        rfqLineItemId: 'line-1',
        sourceDescription: 'Pump assembly',
        sourceUnitPrice: '110.50',
        sourceUom: 'EA',
        sourceQuantity: '2',
      },
    ],
  },
});

const createComparisonRunMatrixData = (): Record<string, unknown> => ({
  id: 'run-42',
  clusters: [
    {
      clusterKey: 'rfq:line-1',
      basis: 'rfq_line_id',
      offers: [
        {
          vendorId: 'vendor-1',
          rfqLineId: 'line-1',
          taxonomyCode: 'PUMP',
          normalizedUnitPrice: 110.5,
          normalizedQuantity: 2,
          aiConfidence: 0.93,
        },
        {
          vendorId: 'vendor-2',
          rfqLineId: 'line-1',
          taxonomyCode: 'PUMP',
          normalizedUnitPrice: 118.2,
          normalizedQuantity: 2,
          aiConfidence: 0.87,
        },
      ],
      statistics: {
        minNormalizedUnitPrice: 110.5,
        maxNormalizedUnitPrice: 118.2,
        avgNormalizedUnitPrice: 114.35,
      },
      recommendation: {
        recommendedVendorId: 'vendor-1',
        reason: 'lowest_normalized_unit_price',
      },
    },
  ],
});

const createComparisonRunReadinessData = (): Record<string, unknown> => ({
  id: 'run-42',
  isReady: true,
  isPreviewOnly: false,
  blockers: [],
  warnings: [{ code: 'LOW_AI_CONFIDENCE', message: 'Confidence below threshold.' }],
});

let comparisonRunData = createComparisonRunData();
let comparisonRunMatrixData = createComparisonRunMatrixData();
let comparisonRunReadinessData = createComparisonRunReadinessData();

vi.mock('@/hooks/use-rfq', () => ({
  useRfq: () => ({ data: { title: 'Cooling Water Pump RFQ', rfq_number: 'RFQ-2026-0042' } }),
}));

vi.mock('@/hooks/use-comparison-run', () => ({
  useComparisonRun: () => ({
    data: comparisonRunData,
    isLoading: false,
    isError: false,
    error: null,
  }),
}));

vi.mock('@/hooks/use-comparison-run-matrix', () => ({
  useComparisonRunMatrix: () => ({
    data: comparisonRunMatrixData,
    isLoading: false,
    isError: false,
    error: null,
  }),
}));

vi.mock('@/hooks/use-comparison-run-readiness', () => ({
  useComparisonRunReadiness: () => ({
    data: comparisonRunReadinessData,
    isLoading: false,
    isError: false,
    error: null,
  }),
}));

import { ComparisonRunDetailPageContent } from './page';

describe('ComparisonRunDetailPage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    comparisonRunData = createComparisonRunData();
    comparisonRunMatrixData = createComparisonRunMatrixData();
    comparisonRunReadinessData = createComparisonRunReadinessData();
  });

  it('renders live run metadata, matrix content, and readiness details', async () => {
    renderWithProviders(<ComparisonRunDetailPageContent rfqId="rfq-1" runId="run-42" />);

    expect(await screen.findByRole('heading', { name: 'Final comparison' })).toBeInTheDocument();
    expect(screen.getByTestId('run-label')).toHaveTextContent('run-42');
    expect(screen.getByText(/snapshot frozen/i)).toBeInTheDocument();
    expect(screen.getAllByText('Vendor One').length).toBeGreaterThan(0);
    expect(screen.getAllByText('Vendor Two').length).toBeGreaterThan(0);
    expect(screen.getAllByText('Pump assembly').length).toBeGreaterThan(0);
    expect(screen.getByText('Confidence below threshold.')).toBeInTheDocument();
    expect(screen.getByRole('link', { name: /decision trail/i })).toBeInTheDocument();
  });

  it('does not render the retired fake controls or sample copy', async () => {
    renderWithProviders(<ComparisonRunDetailPageContent rfqId="rfq-1" runId="run-42" />);

    await screen.findByRole('heading', { name: 'Final comparison' });

    expect(screen.queryByRole('button', { name: /unlock/i })).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /lock/i })).not.toBeInTheDocument();
    expect(screen.queryByText(/Dell Technologies/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/Scoring model: v2.1/i)).not.toBeInTheDocument();
  });

  it('renders safe fallback values when the matrix payload omits numbers', async () => {
    comparisonRunMatrixData = {
      id: 'run-42',
      clusters: [
        {
          clusterKey: 'rfq:line-1',
          basis: 'rfq_line_id',
          offers: [
            {
              vendorId: 'vendor-1',
              rfqLineId: 'line-1',
              taxonomyCode: 'PUMP',
            },
          ],
          statistics: {},
          recommendation: {
            recommendedVendorId: 'vendor-1',
            reason: 'lowest_normalized_unit_price',
          },
        },
      ],
    };

    renderWithProviders(<ComparisonRunDetailPageContent rfqId="rfq-1" runId="run-42" />);

    expect(await screen.findByRole('heading', { name: 'Final comparison' })).toBeInTheDocument();
    expect(screen.getAllByText('—').length).toBeGreaterThan(2);
  });
});
