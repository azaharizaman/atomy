import { describe, expect, it } from 'vitest';
import { normalizeApprovalSummary } from '@/hooks/use-approval-summary';

describe('normalizeApprovalSummary', () => {
  it('normalizes provider summary payloads with provenance when present', () => {
    expect(
      normalizeApprovalSummary({
        data: {
          ai_summary: {
            feature_key: 'approval_ai_summary',
            available: true,
            payload: {
              headline: 'Approval can proceed with the frozen comparison evidence.',
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
      }),
    ).toEqual({
      featureKey: 'approval_ai_summary',
      available: true,
      payload: {
        headline: 'Approval can proceed with the frozen comparison evidence.',
        provenance: {
          provider: 'openrouter',
          endpoint_group: 'comparison_award',
        },
      },
      provenance: {
        provider: 'openrouter',
        endpoint_group: 'comparison_award',
      },
    });
  });

  it('normalizes snake_case payload provenance when top-level provenance is absent', () => {
    expect(
      normalizeApprovalSummary({
        data: {
          ai_summary: {
            feature_key: 'approval_ai_summary',
            available: true,
            payload: {
              recommendation: 'approve',
              provenance: {
                source: 'provider',
              },
            },
          },
        },
      }),
    ).toEqual({
      featureKey: 'approval_ai_summary',
      available: true,
      payload: {
        recommendation: 'approve',
        provenance: {
          source: 'provider',
        },
      },
      provenance: {
        source: 'provider',
      },
    });
  });

  it('returns null provenance when provenance is missing everywhere', () => {
    expect(
      normalizeApprovalSummary({
        data: {
          ai_summary: {
            feature_key: 'approval_ai_summary',
            available: true,
            payload: {
              recommendation: 'approve',
            },
          },
        },
      }),
    ).toEqual({
      featureKey: 'approval_ai_summary',
      available: true,
      payload: {
        recommendation: 'approve',
      },
      provenance: null,
    });
  });

  it('rejects non-boolean available values', () => {
    expect(() =>
      normalizeApprovalSummary({
        data: {
          ai_summary: {
            feature_key: 'approval_ai_summary',
            available: 'yes',
            payload: {},
          },
        },
      }),
    ).toThrow('Approval summary payload has invalid available state.');
  });

  it('rejects missing feature_key values', () => {
    expect(() =>
      normalizeApprovalSummary({
        data: {
          ai_summary: {
            available: true,
            payload: {},
          },
        },
      }),
    ).toThrow('Approval summary payload is missing feature_key.');
  });

  it('rejects non-string feature_key values', () => {
    expect(() =>
      normalizeApprovalSummary({
        data: {
          ai_summary: {
            feature_key: 123,
            available: true,
            payload: {},
          },
        },
      }),
    ).toThrow('Approval summary payload is missing feature_key.');
  });
});
