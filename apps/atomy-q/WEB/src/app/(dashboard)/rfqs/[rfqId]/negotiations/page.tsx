'use client';

import React from 'react';
import { PageHeader } from '@/components/ds/FilterBar';
import { SectionCard, EmptyState } from '@/components/ds/Card';
import { DataTable, type ColumnDef } from '@/components/ds/DataTable';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';
import { useRfq } from '@/hooks/use-rfq';
import { HandCoins } from 'lucide-react';

type NegotiationRow = { id: string; vendor: string; status: string; lastActivity: string };
const MOCK_NEGOTIATIONS: NegotiationRow[] = [];

export default function NegotiationsListPage({ params }: { params: Promise<{ rfqId: string }> }) {
  const { rfqId } = React.use(params);
  const { data: rfq } = useRfq(rfqId);

  const breadcrumbItems = [
    { label: 'RFQs', href: '/rfqs' },
    { label: rfq?.title ?? 'Requisition', href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
    { label: 'Negotiations' },
  ];

  const columns: ColumnDef<NegotiationRow>[] = [
    { key: 'id', label: 'ID', render: (row) => <span className="font-mono text-sm">{row.id}</span> },
    { key: 'vendor', label: 'Vendor', render: (row) => <span className="text-sm font-medium text-slate-800">{row.vendor}</span> },
    { key: 'status', label: 'Status', render: (row) => <span className="text-sm text-slate-600">{row.status}</span> },
    { key: 'lastActivity', label: 'Last activity', render: (row) => <span className="text-xs text-slate-500">{row.lastActivity}</span> },
  ];

  return (
    <div className="space-y-5">
      <WorkspaceBreadcrumbs items={breadcrumbItems} />
      <PageHeader title="Negotiations" subtitle="Vendor negotiation threads for this RFQ" />
      <DataTable
        columns={columns}
        rows={MOCK_NEGOTIATIONS}
        emptyState={
          <EmptyState
            icon={<HandCoins size={20} />}
            title="No negotiations yet"
            description="Launch a negotiation from the Award or Comparison view to start a thread with a vendor."
          />
        }
      />
    </div>
  );
}
