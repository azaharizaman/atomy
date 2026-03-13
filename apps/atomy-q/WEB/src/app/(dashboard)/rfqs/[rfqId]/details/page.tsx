'use client';

import React from 'react';
import { PageHeader } from '@/components/ds/FilterBar';
import { SectionCard, Card, InfoGrid } from '@/components/ds/Card';
import { Button } from '@/components/ds/Button';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';
import { useRfq } from '@/hooks/use-rfq';
import { SquarePen } from 'lucide-react';

export default function RfqDetailsPage({ params }: { params: Promise<{ rfqId: string }> }) {
  const { rfqId } = React.use(params);
  const { data: rfq } = useRfq(rfqId);

  const breadcrumbItems = [
    { label: 'RFQs', href: '/rfqs' },
    { label: rfq?.title ?? 'Requisition', href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
    { label: 'Details' },
  ];

  const metadata = [
    { label: 'Category', value: 'IT Hardware' },
    { label: 'Department', value: 'Procurement' },
    { label: 'Deadline', value: '2026-04-15' },
    { label: 'Evaluation', value: 'Best value' },
    { label: 'Payment terms', value: 'Net 30' },
    { label: 'Estimated value', value: rfq?.estValue ?? '—' },
  ];

  return (
    <div className="space-y-5">
      <WorkspaceBreadcrumbs items={breadcrumbItems} />
      <PageHeader
        title="RFQ details"
        subtitle="Metadata, terms, and deadlines"
        actions={
          <Button variant="outline" size="sm">
            <SquarePen size={14} className="mr-1.5" />
            Edit
          </Button>
        }
      />
      <div className="grid gap-5 xl:grid-cols-[1fr,360px]">
        <SectionCard title="Commercial metadata">
          <InfoGrid cols={2} items={metadata} />
        </SectionCard>
        <SectionCard title="Description">
          <p className="text-sm leading-6 text-slate-600">
            {rfq?.title
              ? `Target specifications and evaluation criteria for ${rfq.title}.`
              : 'No description provided.'}
          </p>
        </SectionCard>
      </div>
    </div>
  );
}
