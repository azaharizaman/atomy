'use client';

import React from 'react';
import { SectionCard, Card } from '@/components/ds/Card';
import { Button } from '@/components/ds/Button';
import { StatusBadge } from '@/components/ds/Badge';
import { RecordHeader } from '@/components/ds/RecordHeader';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';
import { useRfq } from '@/hooks/use-rfq';

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

export default function ApprovalDetailPage({
  params,
}: {
  params: Promise<{ rfqId: string; approvalId: string }>;
}) {
  const { rfqId } = React.use(params);
  const { data: rfq } = useRfq(rfqId);

  const breadcrumbItems = [
    { label: 'RFQs', href: '/rfqs' },
    { label: rfq?.title ?? 'Requisition', href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
    { label: 'Approvals', href: `/rfqs/${encodeURIComponent(rfqId)}/approvals` },
    { label: 'APR-00412' },
  ];

  return (
    <div className="space-y-5">
      <WorkspaceBreadcrumbs items={breadcrumbItems} />
      <RecordHeader
        title="APR-00412"
        status="pending"
        metadata={[
          { label: 'Type', value: 'Comparison Approval' },
          { label: 'SLA', value: '1d 18h' },
          { label: 'Assigned to', value: <OwnerCell name="Marcus Webb" /> },
        ]}
      />
      <div className="grid gap-5 xl:grid-cols-[1.6fr,1fr]">
        <div className="space-y-5">
          <div className="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3">
            <p className="text-xs font-semibold uppercase text-amber-800 mb-2">Approval gate reasons</p>
            <ul className="text-sm text-slate-700 list-disc list-inside space-y-1">
              <li>High risk detected for vendor Cisco Systems</li>
              <li>Top vendor score 68.5 is below auto-approval threshold 70.0</li>
            </ul>
          </div>
          <SectionCard title="Comparison run summary" subtitle="Excerpt">
            <p className="text-xs text-slate-500">3 vendors, 3 key metrics. Recommended: Dell Technologies.</p>
          </SectionCard>
        </div>
        <div className="space-y-5">
          <SectionCard title="Evidence" subtitle="Documents tab">
            <p className="text-xs text-slate-500">3 documents attached.</p>
          </SectionCard>
          <SectionCard title="Decision">
            <div className="space-y-2">
              <Button size="sm" variant="primary" className="w-full justify-center bg-green-600 hover:bg-green-700">
                Approve
              </Button>
              <Button size="sm" variant="outline" className="w-full justify-center border-red-200 text-red-700">
                Reject
              </Button>
              <Button size="sm" variant="outline" className="w-full justify-center border-amber-200 text-amber-700">
                Return for Revision
              </Button>
              <textarea
                placeholder="Provide your decision rationale..."
                className="w-full mt-3 rounded border border-slate-200 px-3 py-2 text-sm min-h-[80px]"
              />
            </div>
          </SectionCard>
        </div>
      </div>
    </div>
  );
}
