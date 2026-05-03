'use client';

import React from 'react';
import { AlertCircle, CheckCircle2, FileArchive, UploadCloud } from 'lucide-react';

import { Button } from '@/components/ds/Button';
import { SectionCard } from '@/components/ds/Card';
import { PageHeader } from '@/components/ds/FilterBar';
import { useEvidenceVault, useEvidenceVaultMutations } from '@/hooks/use-evidence-vault';
import { useRfq } from '@/hooks/use-rfq';

import type { EvidenceVaultSummary } from '@/hooks/use-evidence-vault';

function formatDateTime(value: string | null): string {
  if (value === null) {
    return 'Pending';
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return date.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}

function formatStatus(value: string): string {
  return value
    .split('_')
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(' ');
}

function statusTone(status: string): string {
  if (status === 'complete' || status === 'finalized' || status === 'draft_ready') {
    return 'border-emerald-200 bg-emerald-50 text-emerald-700';
  }

  if (status === 'missing' || status === 'not_ready') {
    return 'border-amber-200 bg-amber-50 text-amber-700';
  }

  return 'border-slate-200 bg-slate-50 text-slate-600';
}

function downloadFileName(rfqNumber: string): string {
  const safeRfqNumber = rfqNumber.trim().replace(/[^A-Za-z0-9._-]+/g, '-').replace(/^-+|-+$/g, '');

  return `${safeRfqNumber || 'rfq'}-evidence-pack.json`;
}

function StatusPill({ status }: { status: string }) {
  return (
    <span className={['rounded-full border px-2 py-0.5 text-[11px] font-semibold', statusTone(status)].join(' ')}>
      {formatStatus(status)}
    </span>
  );
}

function ReadinessBanner({ summary }: { summary: EvidenceVaultSummary }) {
  if (summary.award_pack.status === 'finalized') {
    return (
      <div className="flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">
        <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0" />
        <div>
          <p className="text-sm font-semibold">Evidence pack finalized</p>
          <p className="text-xs text-emerald-700">
            Bundle {summary.award_pack.bundle_id ?? 'pending'} is immutable and ready for audit export.
          </p>
        </div>
      </div>
    );
  }

  if (summary.readiness.ready) {
    return (
      <div className="flex items-start gap-3 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sky-800">
        <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0" />
        <div>
          <p className="text-sm font-semibold">Evidence pack ready for finalization</p>
          <p className="text-xs text-sky-700">
            Required quote, comparison, award, approval, and decision trail evidence is available.
          </p>
        </div>
      </div>
    );
  }

  return (
    <div className="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-800">
      <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" />
      <div>
        <p className="text-sm font-semibold">Evidence pack is not ready</p>
        <p className="text-xs text-amber-700">
          Resolve the blocker checklist before this RFQ can produce an award justification pack.
        </p>
      </div>
    </div>
  );
}

function EvidenceSectionItems({ items }: { items: unknown[] }) {
  if (items.length === 0) {
    return <p className="text-xs text-slate-400">No evidence items captured yet.</p>;
  }

  return (
    <ul className="space-y-1.5">
      {items.map((item, index) => {
        const record = typeof item === 'object' && item !== null ? (item as Record<string, unknown>) : null;
        const label = record?.label !== undefined ? String(record.label) : `Evidence item ${index + 1}`;
        const count = record?.count !== undefined ? String(record.count) : null;

        return (
          <li key={`${label}-${index}`} className="flex items-center justify-between rounded-md bg-slate-50 px-3 py-2">
            <span className="text-xs font-medium text-slate-700">{label}</span>
            {count !== null && <span className="text-xs text-slate-500">{count}</span>}
          </li>
        );
      })}
    </ul>
  );
}

function SupportingEvidenceForm({
  onSubmit,
  isPending,
}: {
  onSubmit: (input: { reason: string; file: File }) => void;
  isPending: boolean;
}) {
  const [reason, setReason] = React.useState('');
  const [file, setFile] = React.useState<File | null>(null);

  function handleSubmit(event: React.FormEvent<HTMLFormElement>): void {
    event.preventDefault();

    if (file === null) {
      return;
    }

    onSubmit({ reason, file });
  }

  return (
    <form onSubmit={handleSubmit} className="rounded-lg border border-slate-200 bg-slate-50 p-4">
      <div className="grid gap-3 md:grid-cols-[1fr_240px_auto]">
        <label className="text-xs font-semibold text-slate-600" htmlFor="supporting-evidence-reason">
          Reason
          <textarea
            id="supporting-evidence-reason"
            value={reason}
            onChange={(event) => setReason(event.target.value)}
            className="mt-1 min-h-20 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            placeholder="Explain why this supporting file belongs in the RFQ evidence vault."
            required
          />
        </label>
        <label className="text-xs font-semibold text-slate-600" htmlFor="supporting-evidence-file">
          File
          <input
            id="supporting-evidence-file"
            type="file"
            onChange={(event) => setFile(event.target.files?.[0] ?? null)}
            className="mt-1 block w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 file:mr-3 file:rounded file:border-0 file:bg-slate-100 file:px-2 file:py-1 file:text-xs file:font-semibold file:text-slate-600"
            required
          />
        </label>
        <div className="flex items-end">
          <Button type="submit" loading={isPending} disabled={file === null || reason.trim() === ''}>
            Upload
          </Button>
        </div>
      </div>
    </form>
  );
}

function EvidenceVaultPageContent({ rfqId }: { rfqId: string }) {
  const { data: rfq } = useRfq(rfqId, { enabled: true });
  const summaryQuery = useEvidenceVault(rfqId);
  const { uploadSupportingEvidence, finalizeAwardPack, exportAwardPack } = useEvidenceVaultMutations(rfqId);
  const [isAttachOpen, setIsAttachOpen] = React.useState(false);
  const [exportError, setExportError] = React.useState<string | null>(null);

  const summary = summaryQuery.data;
  const title = summary?.rfq.title ?? rfq?.title ?? 'RFQ evidence vault';
  const rfqNumber = summary?.rfq.rfq_number ?? rfq?.rfq_number ?? rfqId;

  function handleSupportingEvidenceUpload(input: { reason: string; file: File }): void {
    uploadSupportingEvidence.mutate(input);
  }

  async function handleExportEvidencePack(): Promise<void> {
    setExportError(null);

    try {
      const result = await exportAwardPack.mutateAsync();
      const blob = new Blob([JSON.stringify(result.manifest, null, 2)], { type: 'application/json' });
      const url = URL.createObjectURL(blob);
      const anchor = document.createElement('a');
      anchor.href = url;
      anchor.download = downloadFileName(rfqNumber);
      document.body.appendChild(anchor);
      anchor.click();
      anchor.remove();
      URL.revokeObjectURL(url);
    } catch (error) {
      setExportError(error instanceof Error ? error.message : 'Unable to export evidence pack.');
    }
  }

  return (
    <div className="space-y-5">
      <PageHeader
        title="Evidence Vault"
        subtitle={`${rfqNumber} · ${title}`}
        actions={
          summary !== undefined ? (
            <>
              <Button
                variant="outline"
                icon={<UploadCloud size={14} />}
                disabled={!summary.actions.can_upload_supporting_evidence}
                onClick={() => setIsAttachOpen((current) => !current)}
              >
                Attach supporting evidence
              </Button>
              <Button
                variant="secondary"
                loading={finalizeAwardPack.isPending}
                disabled={!summary.actions.can_finalize}
                onClick={() => finalizeAwardPack.mutate()}
              >
                Finalize award pack
              </Button>
              <Button
                loading={exportAwardPack.isPending}
                disabled={!summary.actions.can_export}
                onClick={() => {
                  void handleExportEvidencePack();
                }}
              >
                Export evidence pack
              </Button>
            </>
          ) : null
        }
      />

      {summaryQuery.isLoading && (
        <SectionCard title="Evidence Vault" subtitle="Loading RFQ evidence readiness">
          <p className="text-sm text-slate-500">Loading evidence vault summary...</p>
        </SectionCard>
      )}

      {summaryQuery.isError && (
        <SectionCard title="Evidence Vault unavailable" subtitle="The RFQ evidence summary could not be loaded">
          <p className="text-sm text-red-600">{summaryQuery.error instanceof Error ? summaryQuery.error.message : 'Unable to load evidence vault.'}</p>
        </SectionCard>
      )}

      {exportError !== null && (
        <SectionCard title="Export unavailable" subtitle="The finalized evidence pack could not be exported">
          <p className="text-sm text-red-600">{exportError}</p>
        </SectionCard>
      )}

      {summary !== undefined && (
        <>
          <ReadinessBanner summary={summary} />

          {isAttachOpen && (
            <SupportingEvidenceForm
              isPending={uploadSupportingEvidence.isPending}
              onSubmit={handleSupportingEvidenceUpload}
            />
          )}

          <div className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
            <div className="space-y-5">
              <SectionCard
                title="Award Justification Pack"
                subtitle="Immutable RFQ evidence bundle for final award approval and audit"
                actions={<StatusPill status={summary.award_pack.status} />}
              >
                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                  <div>
                    <p className="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Bundle ID</p>
                    <p className="mt-1 text-sm font-medium text-slate-800">{summary.award_pack.bundle_id ?? 'Not finalized'}</p>
                  </div>
                  <div>
                    <p className="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Version</p>
                    <p className="mt-1 text-sm font-medium text-slate-800">{summary.award_pack.version ?? 'Pending'}</p>
                  </div>
                  <div>
                    <p className="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Finalized</p>
                    <p className="mt-1 text-sm font-medium text-slate-800">{formatDateTime(summary.award_pack.finalized_at)}</p>
                  </div>
                  <div>
                    <p className="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Checksum</p>
                    <p className="mt-1 break-all text-sm font-medium text-slate-800">{summary.award_pack.checksum ?? 'Pending'}</p>
                  </div>
                </div>
              </SectionCard>

              <SectionCard title="Evidence Sections" subtitle="RFQ-scoped source material included in the readiness check">
                <div className="grid gap-3 md:grid-cols-2">
                  {summary.sections.map((section) => (
                    <div key={section.code} className="rounded-lg border border-slate-200 p-3">
                      <div className="mb-3 flex items-center justify-between gap-3">
                        <p className="text-sm font-semibold text-slate-800">{section.label}</p>
                        <StatusPill status={section.status} />
                      </div>
                      <EvidenceSectionItems items={section.items} />
                    </div>
                  ))}
                </div>
              </SectionCard>
            </div>

            <div className="space-y-5">
              <SectionCard title="Readiness Blockers" subtitle="Required gates before finalization">
                {summary.readiness.blockers.length === 0 ? (
                  <div className="flex items-start gap-2 rounded-md bg-emerald-50 px-3 py-2 text-emerald-700">
                    <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0" />
                    <p className="text-sm font-medium">All evidence gates are satisfied.</p>
                  </div>
                ) : (
                  <ul className="space-y-2">
                    {summary.readiness.blockers.map((blocker) => (
                      <li key={blocker.code} className="rounded-md border border-amber-200 bg-amber-50 px-3 py-2">
                        <p className="text-sm font-semibold text-amber-800">{blocker.message}</p>
                        <p className="mt-0.5 text-xs text-amber-700">{blocker.code}</p>
                      </li>
                    ))}
                  </ul>
                )}
              </SectionCard>

              <SectionCard title="Evidence Timeline" subtitle="Latest captured milestone per evidence gate">
                <ol className="space-y-3">
                  {summary.timeline.map((item) => (
                    <li key={item.code} className="flex gap-3">
                      <div className="mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white">
                        <FileArchive className="h-3.5 w-3.5 text-slate-400" />
                      </div>
                      <div>
                        <div className="flex flex-wrap items-center gap-2">
                          <p className="text-sm font-semibold text-slate-800">{item.label}</p>
                          <StatusPill status={item.status} />
                        </div>
                        <p className="mt-0.5 text-xs text-slate-500">{formatDateTime(item.occurred_at)}</p>
                      </div>
                    </li>
                  ))}
                </ol>
              </SectionCard>
            </div>
          </div>
        </>
      )}
    </div>
  );
}

export default function RfqDocumentsPage({ params }: { params: Promise<{ rfqId: string }> }) {
  const { rfqId } = React.use(params);

  return <EvidenceVaultPageContent rfqId={rfqId} />;
}
