'use client';

import React from 'react';
import { PageHeader } from '@/components/ds/FilterBar';
import { SectionCard, EmptyState } from '@/components/ds/Card';
import { useRfq } from '@/hooks/use-rfq';
import { FolderArchive } from 'lucide-react';

export default function RfqDocumentsPage({ params }: { params: Promise<{ rfqId: string }> }) {
  const { rfqId } = React.use(params);
  useRfq(rfqId);

  return (
    <div className="space-y-5">
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
