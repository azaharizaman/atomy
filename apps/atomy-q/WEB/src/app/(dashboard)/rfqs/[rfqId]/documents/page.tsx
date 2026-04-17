'use client';

import React from 'react';
import { AlphaDeferredScreen } from '@/components/alpha/alpha-deferred-screen';
import { PageHeader } from '@/components/ds/FilterBar';
import { SectionCard, EmptyState } from '@/components/ds/Card';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';
import { useRfq } from '@/hooks/use-rfq';
import { FolderArchive } from 'lucide-react';
import { isAlphaMode } from '@/lib/alpha-mode';

function RfqDocumentsPageContent({ params }: { params: Promise<{ rfqId: string }> }) {
  const { rfqId } = React.use(params);
  const { data: rfq } = useRfq(rfqId);

  const breadcrumbItems = [
    { label: 'RFQs', href: '/rfqs' },
    { label: rfq?.title ?? 'Requisition', href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
    { label: 'Documents' },
  ];

  return (
    <div className="space-y-5">
      <WorkspaceBreadcrumbs items={breadcrumbItems} />
      <PageHeader title="Documents" subtitle="Vault and attachments for this RFQ" />
      <SectionCard title="Document vault">
        <EmptyState
          icon={<FolderArchive size={20} />}
          title="No documents yet"
          description="Uploaded quotes and generated reports will appear here."
        />
      </SectionCard>
    </div>
  );
}

export default function RfqDocumentsPage({ params }: { params: Promise<{ rfqId: string }> }) {
  if (isAlphaMode()) {
    return <AlphaDeferredScreen title="RFQ Documents" subtitle="RFQ document vault is deferred in alpha." />;
  }

  return <RfqDocumentsPageContent params={params} />;
}
