'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import { PageHeader } from '@/components/ds/FilterBar';
import { StatusBadge } from '@/components/ds/Badge';
import { DataTable, type ColumnDef } from '@/components/ds/DataTable';
import { EmptyState, InfoGrid, SectionCard } from '@/components/ds/Card';
import { AiStatusChip } from '@/components/ai/ai-status-chip';
import { AiUnavailableCallout } from '@/components/ai/ai-unavailable-callout';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';
import { useAiStatus } from '@/hooks/use-ai-status';
import { useRfq } from '@/hooks/use-rfq';
import { useApprovalsList } from '@/hooks/use-approvals';
import { useApprovalSummary } from '@/hooks/use-approval-summary';
import { ShieldCheck } from 'lucide-react';

type ApprovalRow = {
  id: string;
  rfqId: string;
  type: string;
  summary: string;
  priority: 'high' | 'medium' | 'low';
  assignee: string;
};

function normalizePriority(priority: string | null | undefined): ApprovalRow['priority'] {
  return priority === 'high' || priority === 'medium' || priority === 'low' ? priority : 'medium';
}

function JsonBlock({ title, value }: { title: string; value: Record<string, unknown> }) {
  return (
    <div className="space-y-2">
      <p className="text-xs font-semibold uppercase tracking-wider text-slate-500">{title}</p>
      <pre className="overflow-x-auto rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs leading-5 text-slate-700">
        {JSON.stringify(value, null, 2)}
      </pre>
    </div>
  );
}

export function ApprovalsListPageContent({ rfqId }: { rfqId: string }) {
  const router = useRouter();
  const { data: rfq } = useRfq(rfqId);
  const aiStatus = useAiStatus();
  const { data } = useApprovalsList({ rfq_id: rfqId, status: 'pending' });
  const scopedItems = React.useMemo(
    () => (data?.items ?? []).filter((a) => a.rfq_id === rfqId),
    [data?.items, rfqId],
  );
  const approvals: ApprovalRow[] = React.useMemo(
    () => scopedItems.map((a) => ({
      id: a.id,
      rfqId: a.rfq_id,
      type: a.type,
      summary: a.summary,
      priority: normalizePriority(a.priority),
      assignee: a.assignee ?? 'Unassigned',
    })),
    [scopedItems],
  );

  const breadcrumbItems = [
    { label: 'RFQs', href: '/rfqs' },
    { label: rfq?.title ?? 'Requisition', href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
    { label: 'Approvals' },
  ];
  const showApprovalSummaryUnavailable = aiStatus.shouldShowUnavailableMessage('approval_ai_summary');
  const hideApprovalSummary = aiStatus.shouldHideAiControls('approval_ai_summary');
  const [selectedApprovalId, setSelectedApprovalId] = React.useState('');
  const approvalIdsKey = React.useMemo(() => approvals.map((approval) => approval.id).join(','), [approvals]);

  React.useEffect(() => {
    if (approvals.length === 0) {
      if (selectedApprovalId !== '') {
        setSelectedApprovalId('');
      }
      return;
    }

    if (selectedApprovalId === '' || approvals.every((approval) => approval.id !== selectedApprovalId)) {
      setSelectedApprovalId(approvals[0].id);
    }
  }, [approvalIdsKey, approvals, selectedApprovalId]);

  const selectedApproval = approvals.find((approval) => approval.id === selectedApprovalId) ?? approvals[0] ?? null;
  const approvalSummaryQuery = useApprovalSummary(selectedApprovalId, {
    enabled: Boolean(selectedApprovalId),
  });
  const approvalSummary = approvalSummaryQuery.data ?? null;
  const isApprovalSummaryUnavailable = approvalSummary?.available === false || showApprovalSummaryUnavailable;

  const columns: ColumnDef<ApprovalRow>[] = [
    {
      key: 'id',
      label: 'ID',
      width: '100px',
      render: (row) => (
        <button
          type="button"
          onClick={(e) => {
            e.stopPropagation();
            router.push(`/rfqs/${encodeURIComponent(rfqId)}/approvals/${encodeURIComponent(row.id)}`);
          }}
          className="text-sm font-medium text-indigo-600 hover:underline"
        >
          {row.id}
        </button>
      ),
    },
    { key: 'type', label: 'Type', width: '140px', render: (row) => <StatusBadge status="pending" label={row.type} /> },
    { key: 'summary', label: 'Summary', render: (row) => <span className="text-sm text-slate-700">{row.summary}</span> },
    { key: 'priority', label: 'Priority', width: '90px', render: (row) => <StatusBadge status="pending" size="xs" label={row.priority} /> },
    { key: 'assignee', label: 'Assignee', width: '120px', render: (row) => <span className="text-sm text-slate-600">{row.assignee}</span> },
  ];

  return (
    <div className="space-y-5">
      <WorkspaceBreadcrumbs items={breadcrumbItems} />
      <PageHeader title="Approvals" subtitle={`${approvals.length} approval(s) for this RFQ`} />
      {!hideApprovalSummary || showApprovalSummaryUnavailable ? (
        <SectionCard
          title="AI summary aid"
          subtitle="Optional provider-derived summary support. The approval list and workflow remain authoritative."
          actions={approvalSummary?.available === true ? <AiStatusChip tone="available" label="AI-derived" /> : null}
        >
          {approvalSummary?.available === true && approvalSummary.payload !== null ? (
            <div className="space-y-4">
              <InfoGrid
                cols={4}
                items={[
                  { label: 'Approval ID', value: selectedApproval?.id ?? '—' },
                  { label: 'Type', value: selectedApproval?.type ?? '—' },
                  { label: 'Priority', value: selectedApproval?.priority ?? '—' },
                  { label: 'Assignee', value: selectedApproval?.assignee ?? '—' },
                ]}
              />
              <JsonBlock title="Provider payload" value={approvalSummary.payload} />
              {approvalSummary.provenance ? (
                <JsonBlock title="Provenance" value={approvalSummary.provenance} />
              ) : null}
            </div>
          ) : isApprovalSummaryUnavailable ? (
            <AiUnavailableCallout
              title="Approval AI summary unavailable"
              messageKey={aiStatus.messageKeyForFeature('approval_ai_summary')}
              fallbackCopy="Approval routing and sign-off remain available without AI summary aid."
            />
          ) : approvalSummaryQuery.isError ? (
            <AiUnavailableCallout
              title="Approval AI summary unavailable"
              fallbackCopy={
                approvalSummaryQuery.error instanceof Error
                  ? approvalSummaryQuery.error.message
                  : 'Approval progression, assignment, and review actions stay manual and deterministic.'
              }
            />
          ) : selectedApproval === null ? (
            <EmptyState
              icon={<ShieldCheck size={20} />}
              title="No pending approvals"
              description="Approval gates will appear here when comparison or risk triggers require sign-off."
            />
          ) : approvalSummaryQuery.isLoading ? (
            <p className="text-sm text-slate-600">Loading approval summary…</p>
          ) : (
            <div className="space-y-2">
              <p className="text-sm text-slate-700">
                Provider help can summarize pending approvals and clarify why the workflow is waiting for sign-off.
              </p>
              <p className="text-xs text-slate-500">
                Approval progression, assignment, and review actions stay manual and deterministic.
              </p>
            </div>
          )}
        </SectionCard>
      ) : null}
      <DataTable
        columns={columns}
        rows={approvals}
        selectable={approvals.length > 0}
        selectedIds={selectedApprovalId !== '' ? [selectedApprovalId] : []}
        onSelectChange={(ids) => {
          setSelectedApprovalId(ids.length > 0 ? String(ids[ids.length - 1]) : '');
        }}
        onRowClick={(row) => router.push(`/rfqs/${encodeURIComponent(rfqId)}/approvals/${encodeURIComponent(row.id)}`)}
        emptyState={
          <EmptyState
            title="No pending approvals"
            description="Approval gates will appear here when comparison or risk triggers require sign-off."
            icon={<ShieldCheck size={20} />}
          />
        }
      />
    </div>
  );
}

export default function ApprovalsListPage({ params }: { params: Promise<{ rfqId: string }> }) {
  const { rfqId } = React.use(params);
  return <ApprovalsListPageContent rfqId={rfqId} />;
}
