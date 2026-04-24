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
    ).toMatchObject({
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
});
