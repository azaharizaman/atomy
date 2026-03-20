'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import { PageHeader } from '@/components/ds/FilterBar';
import { FilterBar } from '@/components/ds/FilterBar';
import { StatusBadge } from '@/components/ds/Badge';
import { SLATimerBadge } from '@/components/ds/Badge';
import { DataTable, type ColumnDef } from '@/components/ds/DataTable';
import { useApprovalsList, type ApprovalListRow } from '@/hooks/use-approvals';
import { Button } from '@/components/ds/Button';

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

export default function ApprovalQueuePage() {
  const router = useRouter();
  const [typeFilter, setTypeFilter] = React.useState('');
  const [statusFilter, setStatusFilter] = React.useState('');
  const [selectedIds, setSelectedIds] = React.useState<(string | number)[]>([]);
  const [page, setPage] = React.useState(1);

  React.useEffect(() => {
    setPage(1);
  }, [typeFilter, statusFilter]);

  const { data, isLoading } = useApprovalsList({
    type: typeFilter || undefined,
    status: statusFilter || undefined,
    page,
  });

  const rows = data?.items ?? [];
  const totalPages = data?.meta?.total_pages ?? 1;
  const totalItems = data?.meta?.total ?? rows.length;

  const columns: ColumnDef<ApprovalListRow>[] = [
    {
      key: 'rfq_id',
      label: 'RFQ',
      render: (row) => (
        <button
          type="button"
          onClick={() => router.push(`/rfqs/${encodeURIComponent(row.rfq_id)}/overview`)}
          className="text-sm font-medium text-indigo-600 hover:underline"
        >
          {row.rfq_title || row.rfq_id}
        </button>
      ),
    },
    {
      key: 'type',
      label: 'Type',
      width: '140px',
      render: (row) => <StatusBadge status="pending" label={row.type_label ?? row.type} />,
    },
    {
      key: 'summary',
      label: 'Summary',
      render: (row) => <span className="text-sm text-slate-700">{row.summary}</span>,
    },
    {
      key: 'priority',
      label: 'Priority',
      width: '90px',
      render: (row) => <StatusBadge status="pending" size="xs" label={row.priority} />,
    },
    {
      key: 'sla',
      label: 'SLA',
      width: '90px',
      render: (row) =>
        row.sla_variant ? (
          <SLATimerBadge variant={row.sla_variant as 'safe' | 'warning' | 'overdue'} value={row.sla ?? '—'} />
        ) : (
          <span className="text-xs text-slate-500">{row.sla ?? '—'}</span>
        ),
    },
    {
      key: 'assignee',
      label: 'Assignee',
      width: '120px',
      render: (row) => <OwnerCell name={row.assignee ?? '—'} />,
    },
  ];

  return (
    <div className="space-y-4">
      <PageHeader title="Approval Queue" subtitle={`${totalItems} approvals`} />
      <FilterBar
        filters={[
          {
            key: 'type',
            label: 'Type',
            value: typeFilter,
            onChange: (v) => setTypeFilter(v ?? ''),
            options: [
              { value: '', label: 'All types' },
              { value: 'comparison_approval', label: 'Comparison approval' },
              { value: 'quote_approval', label: 'Quote approval' },
            ],
          },
          {
            key: 'status',
            label: 'Status',
            value: statusFilter,
            onChange: (v) => setStatusFilter(v ?? ''),
            options: [
              { value: '', label: 'All' },
              { value: 'pending', label: 'Pending' },
              { value: 'approved', label: 'Approved' },
              { value: 'rejected', label: 'Rejected' },
            ],
          },
        ]}
      />
      <DataTable
        columns={columns}
        rows={rows}
        loading={isLoading}
        selectable
        selectedIds={selectedIds}
        onSelectChange={setSelectedIds}
        bulkActions={[
          { label: 'Bulk Approve', onClick: () => {} },
          { label: 'Bulk Reject', onClick: () => {}, variant: 'destructive' },
          { label: 'Bulk Reassign', onClick: () => {} },
        ]}
        onRowClick={(row) => router.push(`/approvals/${encodeURIComponent(row.id)}`)}
      />
      <div className="flex items-center justify-between text-xs text-slate-500">
        <span>
          Page {data?.meta?.current_page ?? page} of {totalPages} ({totalItems} total)
        </span>
        <div className="flex gap-2">
          <Button size="sm" variant="outline" onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={page <= 1}>
            Previous
          </Button>
          <Button
            size="sm"
            variant="outline"
            onClick={() => setPage((p) => p + 1)}
            disabled={page >= totalPages}
          >
            Next
          </Button>
        </div>
      </div>
    </div>
  );
}
