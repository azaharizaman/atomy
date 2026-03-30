'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import { PageHeader } from '@/components/ds/FilterBar';
import { StatusBadge } from '@/components/ds/Badge';
import { DataTable, type ColumnDef } from '@/components/ds/DataTable';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';
import { useRfq } from '@/hooks/use-rfq';
import { useApprovalsList } from '@/hooks/use-approvals';
import { EmptyState } from '@/components/ds/Card';
import { ShieldCheck } from 'lucide-react';

type ApprovalRow = {
  id: string;
  rfqId: string;
  type: string;
  summary: string;
  priority: 'high' | 'medium' | 'low';
  assignee: string;
};

const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

export default function ApprovalsListPage({ params }: { params: Promise<{ rfqId: string }> }) {
  const router = useRouter();
  const { rfqId } = React.use(params);
  const { data: rfq } = useRfq(rfqId);
  const { data } = useApprovalsList({ rfq_id: rfqId, status: 'pending' });
  const approvals: ApprovalRow[] = useMocks
    ? (data?.items ?? []).map((a) => ({
        id: a.id,
        rfqId: a.rfq_id,
        type: a.type,
        summary: a.summary,
        priority: (a.priority as ApprovalRow['priority']) ?? 'medium',
        assignee: a.assignee ?? 'Unassigned',
      }))
    : (data?.items ?? []).map((a) => ({
        id: a.id,
        rfqId: a.rfq_id,
        type: a.type,
        summary: a.summary,
        priority: (a.priority as ApprovalRow['priority']) ?? 'medium',
        assignee: a.assignee ?? 'Unassigned',
      }));

  const breadcrumbItems = [
    { label: 'RFQs', href: '/rfqs' },
    { label: rfq?.title ?? 'Requisition', href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
    { label: 'Approvals' },
  ];

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
      <DataTable
        columns={columns}
        rows={approvals}
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
