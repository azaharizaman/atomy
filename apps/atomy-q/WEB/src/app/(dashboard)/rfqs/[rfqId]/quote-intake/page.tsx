'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import { PageHeader } from '@/components/ds/FilterBar';
import { Card, UploadZone, EmptyState } from '@/components/ds/Card';
import { Button } from '@/components/ds/Button';
import { StatusBadge } from '@/components/ds/Badge';
import { ConfidenceBadge } from '@/components/ds/Badge';
import { DataTable, type ColumnDef } from '@/components/ds/DataTable';
import { FilterBar } from '@/components/ds/FilterBar';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';
import { useRfq } from '@/hooks/use-rfq';
import { getSeedQuotesByRfqId } from '@/data/seed';
import { Plus, Mail, FileText } from 'lucide-react';

type QuoteRow = {
  id: string;
  fileName: string;
  vendor: string;
  status: 'accepted' | 'parsed' | 'processing' | 'rejected' | 'error';
  confidence: 'high' | 'medium' | 'low';
  uploadedAt: string;
};

const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

function useQuoteRows(rfqId: string): QuoteRow[] {
  if (!useMocks) return [];
  const quotes = getSeedQuotesByRfqId(rfqId);
  return quotes.map((q) => ({
    id: q.id,
    fileName: q.fileName,
    vendor: q.vendorName,
    status: q.status,
    confidence: q.confidence,
    uploadedAt: q.uploadedAt,
  }));
}

export default function QuoteIntakeListPage({ params }: { params: Promise<{ rfqId: string }> }) {
  const router = useRouter();
  const { rfqId } = React.use(params);
  const { data: rfq } = useRfq(rfqId);
  const allRows = useQuoteRows(rfqId);
  const [statusFilter, setStatusFilter] = React.useState('');
  const [vendorFilter, setVendorFilter] = React.useState('');

  const breadcrumbItems = [
    { label: 'RFQs', href: '/rfqs' },
    { label: rfq?.title ?? 'Requisition', href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
    { label: 'Quote Intake' },
  ];

  const rows = allRows.filter((q) => {
    if (statusFilter && q.status !== statusFilter) return false;
    if (vendorFilter && q.vendor !== vendorFilter) return false;
    return true;
  });

  const columns: ColumnDef<QuoteRow>[] = [
    {
      key: 'fileName',
      label: 'File name',
      render: (row) => (
        <div className="flex items-center gap-2">
          <FileText size={14} className="text-slate-400 shrink-0" />
          <span className="text-sm font-medium text-slate-800">{row.fileName}</span>
        </div>
      ),
    },
    { key: 'vendor', label: 'Vendor', render: (row) => <span className="text-sm text-slate-700">{row.vendor}</span> },
    {
      key: 'status',
      label: 'Status',
      width: '110px',
      render: (row) => (
        <StatusBadge
          status={
            row.status === 'accepted' ? 'approved' : row.status === 'parsed' ? 'preview' : row.status === 'processing' ? 'processing' : row.status === 'rejected' ? 'rejected' : 'error'
          }
          label={row.status.charAt(0).toUpperCase() + row.status.slice(1)}
        />
      ),
    },
    {
      key: 'confidence',
      label: 'Parse confidence',
      width: '120px',
      render: (row) => <ConfidenceBadge variant={row.confidence} />,
    },
    { key: 'uploadedAt', label: 'Uploaded at', width: '100px', render: (row) => <span className="text-xs text-slate-500">{row.uploadedAt}</span> },
  ];

  return (
    <div className="space-y-5">
      <WorkspaceBreadcrumbs items={breadcrumbItems} />
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
        <UploadZone compact onBrowse={() => {}} />
      </Card>
      <FilterBar
        filters={[
          {
            key: 'status',
            label: 'Status',
            value: statusFilter,
            onChange: (v) => setStatusFilter(v ?? ''),
            options: [
              { value: '', label: 'All statuses' },
              { value: 'accepted', label: 'Accepted' },
              { value: 'parsed', label: 'Parsed' },
              { value: 'processing', label: 'Processing' },
              { value: 'rejected', label: 'Rejected' },
              { value: 'error', label: 'Error' },
            ],
          },
          {
            key: 'vendor',
            label: 'Vendor',
            value: vendorFilter,
            onChange: (v) => setVendorFilter(v ?? ''),
            options: [
              { value: '', label: 'All vendors' },
              ...Array.from(new Set(allRows.map((q) => q.vendor))).map((v) => ({ value: v, label: v })),
            ],
          },
        ]}
      />
      <DataTable
        columns={columns}
        rows={rows}
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
