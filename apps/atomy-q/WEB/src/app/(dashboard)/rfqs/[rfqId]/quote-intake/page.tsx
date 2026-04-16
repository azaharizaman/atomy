'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import { PageHeader } from '@/components/ds/FilterBar';
import { Card, UploadZone, EmptyState } from '@/components/ds/Card';
import { Button } from '@/components/ds/Button';
import { StatusBadge } from '@/components/ds/Badge';
import { ConfidenceBadge } from '@/components/ds/Badge';
import type { StatusVariant } from '@/components/ds/tokens';
import { DataTable, type ColumnDef } from '@/components/ds/DataTable';
import { FilterBar } from '@/components/ds/FilterBar';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';
import { useRfq } from '@/hooks/use-rfq';
import { useNormalizationReview } from '@/hooks/use-normalization-review';
import { useQuoteSubmissions, type QuoteSubmissionRow } from '@/hooks/use-quote-submissions';
import { Plus, Mail, FileText } from 'lucide-react';

const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

function mapQuoteStatus(status: string | undefined): { badge: StatusVariant; label: string } {
  const normalized = (status ?? '').toLowerCase();
  if (normalized === 'ready' || normalized === 'accepted') {
    return { badge: 'approved', label: 'Ready' };
  }
  if (normalized === 'needs_review') {
    return { badge: 'pending', label: 'Needs review' };
  }
  if (normalized === 'failed' || normalized === 'error') {
    return { badge: 'error', label: 'Failed' };
  }
  if (normalized === 'uploaded' || normalized === 'extracting' || normalized === 'extracted' || normalized === 'normalizing') {
    return { badge: 'processing', label: 'Processing' };
  }
  return { badge: 'pending', label: status ? status : 'Pending' };
}

function getConfidenceVariant(confidence: unknown): 'high' | 'medium' | 'low' {
  if (confidence === 'high' || confidence === 'medium' || confidence === 'low') {
    return confidence;
  }
  return 'medium';
}

const STATUS_FILTER_OPTIONS = [
  { value: '', label: 'All statuses' },
  { value: 'approved', label: 'Ready' },
  { value: 'processing', label: 'Processing' },
  { value: 'pending', label: 'Needs review' },
  { value: 'error', label: 'Failed' },
] as const;

export function QuoteIntakeListContent({ rfqId }: { rfqId: string }) {
  const router = useRouter();
  const rfqQuery = useRfq(rfqId);
  const rfq = rfqQuery.data;
  const norm = useNormalizationReview(rfqId, { enabled: !useMocks });
  const submissionsQuery = useQuoteSubmissions(rfqId);
  const submissions = submissionsQuery.data ?? [];
  const [statusFilter, setStatusFilter] = React.useState('');
  const [vendorFilter, setVendorFilter] = React.useState('');

  const breadcrumbItems = [
    { label: 'RFQs', href: '/rfqs' },
    { label: rfq?.title ?? 'Requisition', href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
    { label: 'Quote Intake' },
  ];

  if (rfqQuery.isError || submissionsQuery.isError || (!useMocks && norm.isError)) {
    const pageError = rfqQuery.error ?? submissionsQuery.error ?? norm.error;
    const errorMessage = pageError instanceof Error ? pageError.message : 'Live quote intake data is unavailable.';
    return (
      <div className="space-y-5">
        <WorkspaceBreadcrumbs items={breadcrumbItems} />
        <PageHeader title="Quote Intake" subtitle="Quote intake unavailable" />
        <Card className="p-4 space-y-2">
          <p className="text-sm text-slate-700">The live quote-intake workspace could not be loaded.</p>
          <p className="text-sm text-red-600">{errorMessage}</p>
        </Card>
      </div>
    );
  }

  const rows = submissions.filter((q) => {
    if (statusFilter && mapQuoteStatus(q.status).badge !== statusFilter) return false;
    if (vendorFilter && q.vendor_name !== vendorFilter) return false;
    return true;
  });

  const columns: ColumnDef<QuoteSubmissionRow>[] = [
    {
      key: 'fileName',
      label: 'File name',
      render: (row) => (
        <div className="flex items-center gap-2">
          <FileText size={14} className="text-slate-400 shrink-0" />
          <span className="text-sm font-medium text-slate-800">{row.file_name}</span>
        </div>
      ),
    },
    { key: 'vendor', label: 'Vendor', render: (row) => <span className="text-sm text-slate-700">{row.vendor_name}</span> },
    {
      key: 'status',
      label: 'Status',
      width: '110px',
      render: (row) => {
        const mapped = mapQuoteStatus(row.status);
        return <StatusBadge status={mapped.badge} label={mapped.label} />;
      },
    },
    {
      key: 'confidence',
      label: 'Parse confidence',
      width: '120px',
      render: (row) => <ConfidenceBadge variant={getConfidenceVariant(row.confidence)} />,
    },
    { key: 'uploadedAt', label: 'Uploaded at', width: '100px', render: (row) => <span className="text-xs text-slate-500">{row.uploaded_at ?? '—'}</span> },
  ];

  return (
    <div className="space-y-5">
      <WorkspaceBreadcrumbs items={breadcrumbItems} />
      {!useMocks && norm.hasBlockingIssues && (
        <Card className="border-amber-200 bg-amber-50 p-3 text-sm text-amber-950">
          <span className="font-semibold">Blocking issues</span> — {norm.blockingIssueCount} open issue(s) in normalization.
          Review the normalize workspace before freezing comparison.
        </Card>
      )}
      <PageHeader
        title="Quote Intake"
        subtitle={`${rows.length} submissions`}
        actions={
          <Button size="sm" variant="primary">
            <Plus size={14} className="mr-1.5" />
            Upload Quote
          </Button>
        }
      />
      <Card padding="none">
        {/* TODO: wire UploadZone browse behavior to the real quote-upload flow once vendor selection exists. */}
        <UploadZone compact />
      </Card>
      <FilterBar
        filters={[
          {
            key: 'status',
            label: 'Status',
            value: statusFilter,
            onChange: (v) => setStatusFilter(v ?? ''),
            options: [...STATUS_FILTER_OPTIONS],
          },
          {
            key: 'vendor',
            label: 'Vendor',
            value: vendorFilter,
            onChange: (v) => setVendorFilter(v ?? ''),
            options: [
              { value: '', label: 'All vendors' },
              ...Array.from(new Set(submissions.map((q) => q.vendor_name))).map((v) => ({ value: v, label: v })),
            ],
          },
        ]}
      />
      <DataTable
        columns={columns}
        rows={rows}
        loading={submissionsQuery.isLoading}
        onRowClick={(row) => router.push(`/rfqs/${encodeURIComponent(rfqId)}/quote-intake/${encodeURIComponent(row.id)}`)}
        emptyState={
          <EmptyState
            title="No submissions yet"
            description="Upload the first vendor quote to start parse and normalization workflow."
            icon={<Mail size={20} />}
            action={<Button size="sm">Upload quote</Button>}
          />
        }
      />
    </div>
  );
}

export default function QuoteIntakeListPage({ params }: { params: Promise<{ rfqId: string }> }) {
  const { rfqId } = React.use(params);
  return <QuoteIntakeListContent rfqId={rfqId} />;
}
