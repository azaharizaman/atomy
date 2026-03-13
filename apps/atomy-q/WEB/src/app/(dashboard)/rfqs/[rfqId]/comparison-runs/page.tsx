'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import { PageHeader } from '@/components/ds/FilterBar';
import { StatusBadge } from '@/components/ds/Badge';
import { VersionChip } from '@/components/ds/Badge';
import { DataTable, type ColumnDef } from '@/components/ds/DataTable';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';
import { useRfq } from '@/hooks/use-rfq';
import { getSeedComparisonRunsByRfqId } from '@/data/seed';
import { Button } from '@/components/ds/Button';
import { Plus } from 'lucide-react';

type RunRow = {
  id: string;
  runId: string;
  date: string;
  type: 'preview' | 'final';
  status: 'generated' | 'stale' | 'locked';
  scoringModel: string;
  createdBy: string;
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

export default function ComparisonRunsListPage({ params }: { params: Promise<{ rfqId: string }> }) {
  const router = useRouter();
  const { rfqId } = React.use(params);
  const { data: rfq } = useRfq(rfqId);
  const runs: RunRow[] = useMocks
    ? getSeedComparisonRunsByRfqId(rfqId).map((r) => ({
        id: r.id,
        runId: r.runId,
        date: r.date,
        type: r.type,
        status: r.status,
        scoringModel: r.scoringModel,
        createdBy: r.createdBy,
      }))
    : [];

  const breadcrumbItems = [
    { label: 'RFQs', href: '/rfqs' },
    { label: rfq?.title ?? 'Requisition', href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
    { label: 'Comparison Runs' },
  ];

  const columns: ColumnDef<RunRow>[] = [
    { key: 'runId', label: 'Run ID', render: (row) => <span className="font-mono text-sm font-medium text-slate-800">{row.runId}</span> },
    { key: 'date', label: 'Date', width: '100px', render: (row) => <span className="text-sm text-slate-600">{row.date}</span> },
    {
      key: 'type',
      label: 'Type',
      width: '90px',
      render: (row) => <StatusBadge status={row.type === 'final' ? 'final' : 'preview'} label={row.type === 'final' ? 'Final' : 'Preview'} />,
    },
    {
      key: 'status',
      label: 'Status',
      width: '100px',
      render: (row) => (
        <StatusBadge
          status={row.status === 'locked' ? 'locked' : row.status === 'stale' ? 'stale' : 'generated'}
          label={row.status.charAt(0).toUpperCase() + row.status.slice(1)}
        />
      ),
    },
    { key: 'scoringModel', label: 'Scoring model', width: '80px', render: (row) => <VersionChip version={row.scoringModel} /> },
    { key: 'createdBy', label: 'Created by', render: (row) => <OwnerCell name={row.createdBy} /> },
  ];

  return (
    <div className="space-y-5">
      <WorkspaceBreadcrumbs items={breadcrumbItems} />
      <PageHeader
        title="Comparison Runs"
        subtitle={`${runs.length} runs`}
        actions={
          <Button size="sm" variant="primary">
            <Plus size={14} className="mr-1.5" />
            New Comparison Run
          </Button>
        }
      />
      <DataTable
        columns={columns}
        rows={runs}
        onRowClick={(row) => router.push(`/rfqs/${encodeURIComponent(rfqId)}/comparison-runs/${encodeURIComponent(row.id)}`)}
      />
    </div>
  );
}
