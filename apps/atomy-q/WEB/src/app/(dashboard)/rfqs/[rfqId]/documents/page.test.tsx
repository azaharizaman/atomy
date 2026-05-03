import React from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { fireEvent, screen } from '@testing-library/react';
import { renderPageWithProviders } from '@/test/utils';

import type { EvidenceVaultSummary } from '@/hooks/use-evidence-vault';
import { useEvidenceVault, useEvidenceVaultMutations } from '@/hooks/use-evidence-vault';

vi.mock('@/hooks/use-rfq', () => ({
  useRfq: () => ({ data: { title: 'RFQ' }, isLoading: false }),
}));

vi.mock('@/hooks/use-evidence-vault', () => ({
  useEvidenceVault: vi.fn(),
  useEvidenceVaultMutations: vi.fn(),
}));

import RfqDocumentsPage from './page';

const blockedSummary: EvidenceVaultSummary = {
  rfq: { id: 'rfq-1', title: 'RFQ', rfq_number: 'RFQ-1' },
  award_pack: {
    status: 'not_ready',
    bundle_id: null,
    version: null,
    finalized_at: null,
    checksum: null,
  },
  readiness: {
    ready: false,
    blockers: [{ code: 'FINAL_COMPARISON_MISSING', message: 'Final comparison missing' }],
  },
  timeline: [
    { code: 'quote_sources', label: 'Quote sources', status: 'complete', occurred_at: '2026-05-03T01:00:00Z' },
    { code: 'final_comparison', label: 'Final comparison', status: 'missing', occurred_at: null },
  ],
  sections: [
    { code: 'quote_sources', label: 'Quote Sources', status: 'complete', items: [{ label: 'Ready quote submissions', count: 2 }] },
    { code: 'final_comparison', label: 'Final Comparison', status: 'missing', items: [] },
  ],
  actions: {
    can_finalize: false,
    can_export: false,
    can_upload_supporting_evidence: true,
  },
};

const finalizedSummary: EvidenceVaultSummary = {
  ...blockedSummary,
  award_pack: {
    status: 'finalized',
    bundle_id: 'bundle-1',
    version: 2,
    finalized_at: '2026-05-03T02:00:00Z',
    checksum: 'abc123',
  },
  readiness: {
    ready: true,
    blockers: [],
  },
  actions: {
    can_finalize: false,
    can_export: true,
    can_upload_supporting_evidence: true,
  },
};

type UseEvidenceVaultReturn = ReturnType<typeof useEvidenceVault>;
type UseEvidenceVaultMutationsReturn = ReturnType<typeof useEvidenceVaultMutations>;

function mockEvidenceVault(summary: EvidenceVaultSummary) {
  vi.mocked(useEvidenceVault).mockReturnValue({
    data: summary,
    isLoading: false,
    isError: false,
    error: null,
  } as unknown as UseEvidenceVaultReturn);

  vi.mocked(useEvidenceVaultMutations).mockReturnValue({
    uploadSupportingEvidence: { mutate: vi.fn(), isPending: false },
    finalizeAwardPack: { mutate: vi.fn(), isPending: false },
    exportAwardPack: { mutateAsync: vi.fn().mockResolvedValue({ manifest: { rfq: { id: 'rfq-1' } } }), isPending: false },
  } as unknown as UseEvidenceVaultMutationsReturn);
}

describe('RfqEvidenceVaultPage', () => {
  const createObjectURL = vi.fn(() => 'blob:evidence-pack');
  const revokeObjectURL = vi.fn();

  beforeEach(() => {
    vi.clearAllMocks();
    vi.stubGlobal('URL', { createObjectURL, revokeObjectURL });
    mockEvidenceVault(blockedSummary);
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('renders blockers and disables finalization when evidence is not ready', async () => {
    await renderPageWithProviders(<RfqDocumentsPage params={Promise.resolve({ rfqId: 'rfq-1' })} />);

    expect(await screen.findByRole('heading', { name: 'Evidence Vault' })).toBeInTheDocument();
    expect(screen.getByText('Award Justification Pack')).toBeInTheDocument();
    expect(screen.getByText('Final comparison missing')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /finalize award pack/i })).toBeDisabled();
  });

  it('enables export for finalized evidence packs', async () => {
    mockEvidenceVault(finalizedSummary);

    await renderPageWithProviders(<RfqDocumentsPage params={Promise.resolve({ rfqId: 'rfq-1' })} />);

    expect(await screen.findByText('Evidence pack finalized')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /export evidence pack/i })).toBeEnabled();
  });

  it('downloads the finalized manifest when export succeeds', async () => {
    mockEvidenceVault(finalizedSummary);
    const appendChild = vi.spyOn(document.body, 'appendChild');
    const click = vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(() => undefined);
    const remove = vi.spyOn(HTMLAnchorElement.prototype, 'remove').mockImplementation(() => undefined);

    await renderPageWithProviders(<RfqDocumentsPage params={Promise.resolve({ rfqId: 'rfq-1' })} />);

    fireEvent.click(await screen.findByRole('button', { name: /export evidence pack/i }));

    expect(await screen.findByText('Evidence pack finalized')).toBeInTheDocument();
    expect(createObjectURL).toHaveBeenCalledWith(expect.any(Blob));
    expect(appendChild).toHaveBeenCalled();
    expect(click).toHaveBeenCalled();
    expect(remove).toHaveBeenCalled();
    expect(revokeObjectURL).toHaveBeenCalledWith('blob:evidence-pack');
  });

  it('opens the supporting evidence drawer fields', async () => {
    await renderPageWithProviders(<RfqDocumentsPage params={Promise.resolve({ rfqId: 'rfq-1' })} />);

    fireEvent.click(screen.getByRole('button', { name: /attach supporting evidence/i }));

    expect(screen.getByLabelText(/reason/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/file/i)).toBeInTheDocument();
  });
});
