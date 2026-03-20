'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import { SectionCard, Card } from '@/components/ds/Card';
import { Button } from '@/components/ds/Button';
import { StatusBadge } from '@/components/ds/Badge';
import { RecordHeader } from '@/components/ds/RecordHeader';
import { PageHeader } from '@/components/ds/FilterBar';
import { useApprovalDetail } from '@/hooks/use-approvals';
import { api } from '@/lib/api';
import { parseApiError } from '@/lib/api-error';
import { toast } from 'sonner';

function OwnerCell({ name }: { name: string }) {
  const initials = name
    .split(' ')
    .filter(Boolean)
    .map((p) => p[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();
  return (
    <div className="flex items-center gap-2">
      <div className="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-xs font-medium text-slate-600">
        {initials}
      </div>
      <span className="text-sm text-slate-700">{name}</span>
    </div>
  );
}

export default function GlobalApprovalDetailPage({ params }: { params: Promise<{ id: string }> }) {
  const router = useRouter();
  const { id } = React.use(params);
  const { data: approval, isLoading, error, refetch } = useApprovalDetail(id);
  const [reason, setReason] = React.useState('');
  const [busy, setBusy] = React.useState(false);

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

  if (error || !approval) {
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
