'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import { SectionCard, Card } from '@/components/ds/Card';
import { Button } from '@/components/ds/Button';
import { StatusBadge } from '@/components/ds/Badge';
import { RecordHeader } from '@/components/ds/RecordHeader';
import { PageHeader } from '@/components/ds/FilterBar';
import { OwnerCell } from '@/components/ds/OwnerCell';
import { useApprovalDetail } from '@/hooks/use-approvals';
import { api } from '@/lib/api';
import { parseApiError } from '@/lib/api-error';
import { toast } from 'sonner';

export default function GlobalApprovalDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const router = useRouter();
  const { id } = React.use(params);
  const { data: approval, isLoading, error, refetch } = useApprovalDetail(id);
  const [reason, setReason] = React.useState('');
  const [busy, setBusy] = React.useState(false);
  const [errorDismissed, setErrorDismissed] = React.useState(false);

  const errParsed = error ? parseApiError(error) : undefined;
  const isNotFound =
    !approval &&
    (errParsed?.status === 404 || (error instanceof Error && error.message === 'Not found'));
  const blockingError = Boolean(error) && !isNotFound;
  const showErrorOverlay = blockingError && !errorDismissed;

  React.useEffect(() => {
    setErrorDismissed(false);
  }, [id]);

  React.useEffect(() => {
    if (!showErrorOverlay) return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') setErrorDismissed(true);
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [showErrorOverlay]);

  const onApprove = async () => {
    if (!approval) return;
    setBusy(true);
    try {
      await api.post(`/approvals/${encodeURIComponent(approval.id)}/approve`, { reason: reason.trim() || 'Approved' });
      toast.success('Approval recorded');
      await refetch();
      router.push('/approvals');
    } catch (e) {
      const parsed = parseApiError(e);
      toast.error(parsed.message || 'Unable to approve');
    } finally {
      setBusy(false);
    }
  };

  const onReject = async () => {
    if (!approval) return;
    setBusy(true);
    try {
      await api.post(`/approvals/${encodeURIComponent(approval.id)}/reject`, { reason: reason.trim() || undefined });
      toast.success('Rejection recorded');
      await refetch();
      router.push('/approvals');
    } catch (e) {
      const parsed = parseApiError(e);
      toast.error(parsed.message || 'Unable to reject');
    } finally {
      setBusy(false);
    }
  };

  if (isLoading) {
    return (
      <div className="space-y-4">
        <PageHeader title="Approval" subtitle="Loading…" />
        <div className="h-48 rounded-lg border border-slate-200 bg-slate-50 animate-pulse" />
      </div>
    );
  }

  if (!isLoading && isNotFound) {
    return (
      <div className="space-y-4">
        <PageHeader title="Approval" subtitle="Not found" />
        <Card className="p-6 text-sm text-slate-600">
          This approval does not exist or you do not have access.
          <div className="mt-4">
            <Button size="sm" variant="outline" onClick={() => router.push('/approvals')}>
              Back to queue
            </Button>
          </div>
        </Card>
      </div>
    );
  }

  if (!isLoading && blockingError && !approval) {
    return (
      <div className="space-y-4 relative">
        <PageHeader title="Approval" subtitle="Unable to load" />
        {showErrorOverlay ? (
          <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
            role="alertdialog"
            aria-modal="true"
            aria-labelledby="approval-error-title"
            aria-describedby="approval-error-desc"
          >
            <Card className="max-w-md w-full p-6 shadow-xl space-y-4">
              <h2 id="approval-error-title" className="text-sm font-semibold text-slate-900">
                Something went wrong
              </h2>
              <p id="approval-error-desc" className="text-sm text-slate-600">
                {errParsed?.message ?? 'Could not load this approval. Check your connection and try again.'}
              </p>
              <p className="text-xs text-slate-500">Press Escape to close this dialog.</p>
              <div className="flex flex-wrap gap-2">
                <Button
                  size="sm"
                  variant="primary"
                  onClick={() => {
                    setErrorDismissed(false);
                    void refetch();
                  }}
                >
                  Retry
                </Button>
                <Button size="sm" variant="outline" onClick={() => setErrorDismissed(true)}>
                  Dismiss
                </Button>
                <Button size="sm" variant="outline" onClick={() => router.push('/approvals')}>
                  Back to queue
                </Button>
              </div>
            </Card>
          </div>
        ) : (
          <Card className="p-6 text-sm text-slate-600 space-y-4">
            <p>{errParsed?.message ?? 'Could not load this approval.'}</p>
            <div className="flex flex-wrap gap-2">
              <Button size="sm" variant="primary" onClick={() => void refetch()}>
                Retry
              </Button>
              <Button size="sm" variant="outline" onClick={() => router.refresh()}>
                Refresh page
              </Button>
              <Button size="sm" variant="outline" onClick={() => router.push('/approvals')}>
                Back to queue
              </Button>
            </div>
          </Card>
        )}
      </div>
    );
  }

  if (!approval) {
    return null;
  }

  const statusForBadge =
    approval.status === 'approved' ? 'approved' : approval.status === 'rejected' ? 'rejected' : 'pending';

  return (
    <div className="space-y-5">
      <div className="flex items-center gap-2 text-sm text-slate-500">
        <button type="button" className="text-indigo-600 hover:underline" onClick={() => router.push('/approvals')}>
          Approval Queue
        </button>
        <span>/</span>
        <span className="font-mono text-xs">{approval.id}</span>
      </div>

      <RecordHeader
        title={approval.type_label ?? approval.type}
        status={statusForBadge}
        metadata={[
          { label: 'RFQ', value: approval.rfq_title || approval.rfq_id },
          { label: 'Requested', value: approval.requested_at ? new Date(approval.requested_at).toLocaleString() : '—' },
          { label: 'Assignee', value: <OwnerCell name={approval.assignee ?? '—'} /> },
        ]}
      />

      <div className="grid gap-5 xl:grid-cols-[1.6fr,1fr]">
        <div className="space-y-5">
          {approval.comparison_run && (
            <SectionCard title="Comparison run" subtitle="Linked snapshot">
              <div className="p-4 pt-0 text-sm text-slate-700 space-y-1">
                <p>
                  <span className="text-slate-500">Name:</span> {approval.comparison_run.name}
                </p>
                <p>
                  <span className="text-slate-500">Status:</span>{' '}
                  <StatusBadge
                    status={approval.comparison_run.is_preview ? 'preview' : 'draft'}
                    label={approval.comparison_run.status}
                  />
                </p>
              </div>
            </SectionCard>
          )}
          {approval.notes ? (
            <SectionCard title="Notes" subtitle="Context">
              <p className="p-4 pt-0 text-sm text-slate-700 whitespace-pre-wrap">{approval.notes}</p>
            </SectionCard>
          ) : null}
        </div>
        <div className="space-y-5">
          {approval.status === 'pending' ? (
            <SectionCard title="Decision">
              <div className="space-y-2 p-4 pt-0">
                <textarea
                  value={reason}
                  onChange={(e) => setReason(e.target.value)}
                  placeholder="Optional rationale…"
                  className="w-full rounded border border-slate-200 px-3 py-2 text-sm min-h-[80px]"
                />
                <Button
                  size="sm"
                  variant="primary"
                  className="w-full justify-center bg-green-600 hover:bg-green-700"
                  loading={busy}
                  onClick={() => void onApprove()}
                >
                  Approve
                </Button>
                <Button
                  size="sm"
                  variant="outline"
                  className="w-full justify-center border-red-200 text-red-700"
                  disabled={busy}
                  onClick={() => void onReject()}
                >
                  Reject
                </Button>
              </div>
            </SectionCard>
          ) : (
            <SectionCard title="Decision">
              <p className="p-4 pt-0 text-sm text-slate-600">No further actions — this approval is {approval.status}.</p>
            </SectionCard>
          )}
        </div>
      </div>
    </div>
  );
}
