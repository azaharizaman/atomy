'use client';

import React from 'react';
import { PageHeader } from '@/components/ds/FilterBar';
import { SectionCard } from '@/components/ds/Card';
import { Timeline, type TimelineEvent } from '@/components/ds/Timeline';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';
import { useRfq } from '@/hooks/use-rfq';
import { CheckCircle2 } from 'lucide-react';

const MOCK_TRAIL: TimelineEvent[] = [
  { id: '1', timestamp: '2 hours ago', actor: 'Marcus Webb', action: 'approved comparison run RUN-005', icon: <CheckCircle2 size={12} />, iconColor: 'green' },
  { id: '2', timestamp: '1 day ago', actor: 'Alex Kumar', action: 'locked run RUN-005 for award', icon: <CheckCircle2 size={12} />, iconColor: 'slate' },
  { id: '3', timestamp: '2 days ago', actor: 'System', action: 'generated comparison run RUN-005', iconColor: 'indigo' },
];

export default function DecisionTrailPage({ params }: { params: Promise<{ rfqId: string }> }) {
  const { rfqId } = React.use(params);
  const { data: rfq } = useRfq(rfqId);

  const breadcrumbItems = [
    { label: 'RFQs', href: '/rfqs' },
    { label: rfq?.title ?? 'Requisition', href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
    { label: 'Decision Trail' },
  ];

  return (
    <div className="space-y-5">
      <WorkspaceBreadcrumbs items={breadcrumbItems} />
      <PageHeader title="Decision Trail" subtitle="Audit log of key decisions for this RFQ" />
      <SectionCard title="Chronological trail">
        <Timeline events={MOCK_TRAIL} compact />
      </SectionCard>
    </div>
  );
}
