'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import { PageHeader } from '@/components/ds/FilterBar';
import { FilterBar } from '@/components/ds/FilterBar';
import { StatusBadge } from '@/components/ds/Badge';
import { SLATimerBadge } from '@/components/ds/Badge';
import { DataTable, type ColumnDef } from '@/components/ds/DataTable';
import { getSeedPendingApprovals } from '@/data/seed';

type ApprovalRow = {
  id: string;
  rfqId: string;
  type: string;
  summary: string;
  priority: 'high' | 'medium' | 'low';
  sla: string;
  slaVariant: 'safe' | 'warning' | 'overdue';
  assignee: string;
};

const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

function OwnerCell({ name }: { name: string }) {
  const initials = name.split(' ').map((p) => p[0]).join('').slice(0, 2).toUpperCase();
  return (
    <div className="flex items-center gap-2">
      <div className="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-xs font-medium text-slate-600">
        {initials}
      </div>
      <span className="text-sm text-slate-700">{name}</span>
    </div>
  );
}

export default function ApprovalQueuePage() {
  const router = useRouter();
  const [typeFilter, setTypeFilter] = React.useState('');
  const [statusFilter, setStatusFilter] = React.useState('');
  const [selectedIds, setSelectedIds] = React.useState<(string | number)[]>([]);

  const allApprovals: ApprovalRow[] = useMocks
    ? getSeedPendingApprovals().map((a) => ({
        id: a.id,
        rfqId: a.rfqId,
        type: a.type,
        summary: a.summary,
        priority: a.priority,
        sla: a.sla ?? '—',
        slaVariant: a.slaVariant ?? 'safe',
        assignee: a.assignee,
      }))
    : [];

  const rows = allApprovals.filter((r) => {
    if (typeFilter && r.type !== typeFilter) return false;
    return true;
  });

  const columns: ColumnDef<ApprovalRow>[] = [
    {
      key: 'rfqId',
      label: 'RFQ',
      render: (row) => (
        <button
          type="button"
          onClick={() => router.push(`/rfqs/${encodeURIComponent(row.rfqId)}/overview`)}
          className="text-sm font-medium text-indigo-600 hover:underline"
        >
          {row.rfqId}
        </button>
      ),
    },
    { key: 'type', label: 'Type', width: '140px', render: (row) => <StatusBadge status="pending" label={row.type} /> },
    { key: 'summary', label: 'Summary', render: (row) => <span className="text-sm text-slate-700">{row.summary}</span> },
    { key: 'priority', label: 'Priority', width: '90px', render: (row) => <StatusBadge status="pending" size="xs" label={row.priority} /> },
    { key: 'sla', label: 'SLA', width: '90px', render: (row) => <SLATimerBadge variant={row.slaVariant} value={row.sla} /> },
    { key: 'assignee', label: 'Assignee', width: '120px', render: (row) => <OwnerCell name={row.assignee} /> },
  ];

  return (
    <div className="space-y-4">
      <PageHeader
        title="Approval Queue"
        subtitle={`${rows.length} pending approvals`}
      />
      <FilterBar
        filters={[
          {
            key: 'type',
            label: 'Type',
            value: typeFilter,
            onChange: (v) => setTypeFilter(v ?? ''),
            options: [
              { value: '', label: 'All types' },
              { value: 'Comparison Approval', label: 'Comparison Approval' },
              { value: 'Risk Escalation', label: 'Risk Escalation' },
              { value: 'Override', label: 'Override' },
            ],
          },
          {
            key: 'status',
            label: 'Status',
            value: statusFilter,
            onChange: (v) => setStatusFilter(v ?? ''),
            options: [
              { value: '', label: 'Pending' },
              { value: 'snoozed', label: 'Snoozed' },
            ],
          },
        ]}
      />
      <DataTable
        columns={columns}
        rows={rows}
        selectable
        selectedIds={selectedIds}
        onSelectChange={setSelectedIds}
        bulkActions={[
          { label: 'Bulk Approve', onClick: () => {} },
          { label: 'Bulk Reject', onClick: () => {}, variant: 'destructive' },
          { label: 'Bulk Reassign', onClick: () => {} },
        ]}
        onRowClick={(row) => router.push(`/rfqs/${encodeURIComponent(row.rfqId)}/approvals/${row.id}`)}
      />
    </div>
  );
}
