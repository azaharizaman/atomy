'use client';

import React from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { PageHeader } from '@/components/ds/FilterBar';
import { EmptyState, SectionCard } from '@/components/ds/Card';
import { StatusBadge } from '@/components/ds/Badge';
import { DataTable, type ColumnDef } from '@/components/ds/DataTable';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';
import { useRfq } from '@/hooks/use-rfq';
import { useComparisonRuns, type ComparisonRunRow } from '@/hooks/use-comparison-runs';
import { Button } from '@/components/ds/Button';
import { AlertTriangle, Layers3, Plus } from 'lucide-react';

export function ComparisonRunsListContent({ rfqId }: { rfqId: string }) {
  const router = useRouter();
  const rfqQuery = useRfq(rfqId);
  const runsQuery = useComparisonRuns(rfqId);
  const rfq = rfqQuery.data;
  const runs = runsQuery.data ?? [];

  const breadcrumbItems = [
    { label: 'RFQs', href: '/rfqs' },
    { label: rfq?.title ?? 'Requisition', href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
    { label: 'Comparison Runs' },
  ];

  if (rfqQuery.isError || runsQuery.isError) {
    const pageError = rfqQuery.error ?? runsQuery.error;
    const errorMessage = pageError instanceof Error ? pageError.message : 'The live comparison-run list could not be loaded.';
    return (
      <div className="space-y-5">
        <WorkspaceBreadcrumbs items={breadcrumbItems} />
        <PageHeader title="Comparison Runs" subtitle="Comparison runs unavailable" />
        <SectionCard title="Comparison runs unavailable">
          <EmptyState
            icon={<AlertTriangle size={20} />}
            title="Could not load comparison runs"
            description={errorMessage}
          />
        </SectionCard>
      </div>
    );
  }

  const columns: ColumnDef<ComparisonRunRow>[] = [
    {
      key: 'name',
      label: 'Run',
      render: (row) => (
        <div className="space-y-0.5">
          <span className="block text-sm font-medium text-slate-800">{row.name}</span>
          <span className="block font-mono text-xs text-slate-500">{row.id}</span>
        </div>
      ),
    },
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
        loading={runsQuery.isLoading}
        onRowClick={(row) => router.push(`/rfqs/${encodeURIComponent(rfqId)}/comparison-runs/${encodeURIComponent(row.id)}`)}
        emptyState={
          <EmptyState
            icon={<Layers3 size={20} />}
            title="No comparison runs yet"
            description="Freeze a comparison run after normalization to generate comparison evidence."
          />
        }
      />
    </div>
  );
}

export default function ComparisonRunsListPage({ params }: { params: Promise<{ rfqId: string }> }) {
  const { rfqId } = React.use(params);
  return <ComparisonRunsListContent rfqId={rfqId} />;
}
