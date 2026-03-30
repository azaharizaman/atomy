'use client';

import React from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { PageHeader } from '@/components/ds/FilterBar';
import { StatusBadge } from '@/components/ds/Badge';
import { VersionChip } from '@/components/ds/Badge';
import { DataTable, type ColumnDef } from '@/components/ds/DataTable';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';
import { useRfq } from '@/hooks/use-rfq';
import { useComparisonRuns, type ComparisonRunRow } from '@/hooks/use-comparison-runs';
import { Button } from '@/components/ds/Button';
import { Plus } from 'lucide-react';

function OwnerCell({ name }: { name: string }) {
  const safeName = name.trim() || 'System';
  const initials = safeName
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
      <span className="text-sm text-slate-700">{safeName}</span>
    </div>
  );
}

export function ComparisonRunsListContent({ rfqId }: { rfqId: string }) {
  const router = useRouter();
  const { data: rfq } = useRfq(rfqId);
  const { data: runs = [] } = useComparisonRuns(rfqId);

  const breadcrumbItems = [
    { label: 'RFQs', href: '/rfqs' },
    { label: rfq?.title ?? 'Requisition', href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
    { label: 'Comparison Runs' },
  ];

  const columns: ColumnDef<ComparisonRunRow>[] = [
    { key: 'runId', label: 'Run ID', render: (row) => <span className="font-mono text-sm font-medium text-slate-800">{row.run_id}</span> },
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
    { key: 'scoringModel', label: 'Scoring model', width: '80px', render: (row) => <VersionChip version={row.scoring_model} /> },
    { key: 'createdBy', label: 'Created by', render: (row) => <OwnerCell name={row.created_by || 'System'} /> },
  ];

  const hasFinalRun = runs.some((r) => r.type === 'final');

  return (
    <div className="space-y-5">
      <WorkspaceBreadcrumbs items={breadcrumbItems} />
      {hasFinalRun && (
        <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 flex flex-wrap items-center justify-between gap-2">
          <p className="text-sm text-slate-800">
            <span className="font-semibold">Snapshot frozen</span>
            <span className="text-slate-600"> — comparison inputs are locked for approval.</span>
          </p>
          <Link
            href={`/rfqs/${encodeURIComponent(rfqId)}/decision-trail`}
            className="text-sm font-medium text-indigo-600 hover:underline"
          >
            Decision trail
          </Link>
        </div>
      )}
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

export default function ComparisonRunsListPage({ params }: { params: Promise<{ rfqId: string }> }) {
  const { rfqId } = React.use(params);
  return <ComparisonRunsListContent rfqId={rfqId} />;
}
