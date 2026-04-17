'use client';

import React from 'react';
import { AlphaDeferredScreen } from '@/components/alpha/alpha-deferred-screen';
import { PageHeader } from '@/components/ds/FilterBar';
import { SectionCard } from '@/components/ds/Card';
import { StatusBadge } from '@/components/ds/Badge';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';
import { useRfq } from '@/hooks/use-rfq';
import { isAlphaMode } from '@/lib/alpha-mode';

function RiskPageContent({ params }: { params: Promise<{ rfqId: string }> }) {
  const { rfqId } = React.use(params);
  const { data: rfq } = useRfq(rfqId, { enabled: true });

  const breadcrumbItems = [
    { label: 'RFQs', href: '/rfqs' },
    { label: rfq?.title ?? 'Requisition', href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
    { label: 'Risk & Compliance' },
  ];

  return (
    <div className="space-y-5">
      <WorkspaceBreadcrumbs items={breadcrumbItems} />
      <PageHeader title="Risk & Compliance" subtitle="Flags and screening results for this RFQ" />
      <SectionCard title="Screening summary">
        <div className="flex items-center gap-2">
          <StatusBadge status="approved" label="No issues" />
          <span className="text-sm text-slate-500">All invited vendors passed screening.</span>
        </div>
      </SectionCard>
    </div>
  );
}

export default function RiskPage({ params }: { params: Promise<{ rfqId: string }> }) {
  if (isAlphaMode()) {
    return <AlphaDeferredScreen title="Risk & Compliance" subtitle="Risk screening is deferred in alpha." />;
  }

  return <RiskPageContent params={params} />;
}
