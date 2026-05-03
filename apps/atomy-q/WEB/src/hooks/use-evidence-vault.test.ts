import { describe, expect, it } from 'vitest';

import { normalizeEvidenceVaultSummary } from '@/hooks/use-evidence-vault';

function validSummaryPayload(overrides: Record<string, unknown> = {}) {
  return {
    data: {
      rfq: { id: 'rfq-1', title: 'RFQ', rfq_number: 'RFQ-1' },
      award_pack: {
        status: 'finalized',
        bundle_id: 'bundle-1',
        version: 1,
        finalized_at: '2026-05-03T01:00:00Z',
        checksum: 'abc123',
      },
      readiness: { ready: true, blockers: [] },
      timeline: [],
      sections: [],
      actions: {
        can_finalize: false,
        can_export: true,
        can_upload_supporting_evidence: true,
      },
      ...overrides,
    },
  };
}

describe('normalizeEvidenceVaultSummary', () => {
  it('accepts numeric strings for optional numeric fields', () => {
    const summary = normalizeEvidenceVaultSummary(validSummaryPayload({
      award_pack: {
        status: 'finalized',
        bundle_id: 'bundle-1',
        version: '2',
        finalized_at: '2026-05-03T01:00:00Z',
        checksum: 'abc123',
      },
    }));

    expect(summary.award_pack.version).toBe(2);
  });

  it('rejects malformed optional text fields', () => {
    expect(() => normalizeEvidenceVaultSummary(validSummaryPayload({
      rfq: { id: 'rfq-1', title: true, rfq_number: 'RFQ-1' },
    }))).toThrow('rfq.title must be a non-empty string or null');
  });

  it('rejects malformed optional number fields', () => {
    expect(() => normalizeEvidenceVaultSummary(validSummaryPayload({
      award_pack: {
        status: 'finalized',
        bundle_id: 'bundle-1',
        version: {},
        finalized_at: '2026-05-03T01:00:00Z',
        checksum: 'abc123',
      },
    }))).toThrow('award_pack.version must be numeric');
  });
});
