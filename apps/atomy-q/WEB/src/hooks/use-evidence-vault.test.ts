import { renderHook, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { createTestWrapper } from '@/test/utils';
import { normalizeEvidenceVaultSummary, useEvidenceVault } from '@/hooks/use-evidence-vault';

const fetchLiveOrFailMock = vi.hoisted(() => vi.fn());

vi.mock('@/lib/api-live', () => ({
  fetchLiveOrFail: fetchLiveOrFailMock,
}));

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
  beforeEach(() => {
    fetchLiveOrFailMock.mockReset();
  });

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

  it('uses the live Evidence Vault response when the payload is valid', async () => {
    fetchLiveOrFailMock.mockResolvedValueOnce(validSummaryPayload({
      award_pack: {
        status: 'draft_ready',
        bundle_id: null,
        version: null,
        finalized_at: null,
        checksum: null,
      },
      readiness: { ready: true, blockers: [] },
      actions: {
        can_finalize: true,
        can_export: false,
        can_upload_supporting_evidence: true,
      },
    }));
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useEvidenceVault('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(fetchLiveOrFailMock).toHaveBeenCalledWith('/rfqs/rfq-1/evidence-vault');
    expect(result.current.data?.award_pack.status).toBe('draft_ready');
    expect(result.current.data?.readiness.ready).toBe(true);
  });

  it('renders empty but valid tenant-scoped Evidence Vault data honestly', async () => {
    fetchLiveOrFailMock.mockResolvedValueOnce(validSummaryPayload({
      award_pack: {
        status: 'not_ready',
        bundle_id: null,
        version: null,
        finalized_at: null,
        checksum: null,
      },
      readiness: { ready: false, blockers: [] },
      timeline: [],
      sections: [],
      actions: {
        can_finalize: false,
        can_export: false,
        can_upload_supporting_evidence: true,
      },
    }));
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useEvidenceVault('rfq-empty'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data?.readiness.ready).toBe(false);
    expect(result.current.data?.sections).toEqual([]);
  });

  it('surfaces live transport failure', async () => {
    fetchLiveOrFailMock.mockRejectedValueOnce(new Error('Evidence Vault API unavailable'));
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useEvidenceVault('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('Evidence Vault API unavailable');
  });

  it('throws when the live Evidence Vault response is undefined', async () => {
    fetchLiveOrFailMock.mockResolvedValueOnce(undefined);
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useEvidenceVault('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('Evidence vault summary unavailable');
  });

  it('rejects malformed live Evidence Vault payloads', async () => {
    fetchLiveOrFailMock.mockResolvedValueOnce(validSummaryPayload({
      sections: [{ code: 'quote_sources', label: 'Quote Sources', status: 'complete' }],
    }));
    const { Wrapper } = createTestWrapper();

    const { result } = renderHook(() => useEvidenceVault('rfq-1'), { wrapper: Wrapper });

    await waitFor(() => expect(result.current.isError).toBe(true));
    expect(result.current.error).toBeInstanceOf(Error);
    expect((result.current.error as Error).message).toContain('sections[0].items must be an array');
  });
});
