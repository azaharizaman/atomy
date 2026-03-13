'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import { SectionCard, Card, DocPreview } from '@/components/ds/Card';
import { Button } from '@/components/ds/Button';
import { RecordHeader } from '@/components/ds/RecordHeader';
import { ConfidenceBadge } from '@/components/ds/Badge';
import { SecondaryTabs } from '@/components/ds/Tabs';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';
import { useRfq } from '@/hooks/use-rfq';
import { CheckCircle2, AlertTriangle } from 'lucide-react';

export default function QuoteIntakeDetailPage({
  params,
}: {
  params: Promise<{ rfqId: string; quoteId: string }>;
}) {
  const router = useRouter();
  const { rfqId, quoteId } = React.use(params);
  const { data: rfq } = useRfq(rfqId);
  const [activeTab, setActiveTab] = React.useState('overview');

  const breadcrumbItems = [
    { label: 'RFQs', href: '/rfqs' },
    { label: rfq?.title ?? 'Requisition', href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
    { label: 'Quote Intake', href: `/rfqs/${encodeURIComponent(rfqId)}/quote-intake` },
    { label: 'Dell Technologies Quote' },
  ];

  const validationItems = [
    { label: 'Line count match', pass: true },
    { label: 'Currency detected', pass: true },
    { label: 'Unit consistency', pass: true },
    { label: 'Lead time present', pass: false, warning: 'Lead time not detected' },
  ];

  return (
    <div className="space-y-5">
      <WorkspaceBreadcrumbs items={breadcrumbItems} />
      <RecordHeader
        title="Dell_Quote_RFQ2401.pdf"
        status="approved"
        metadata={[
          { label: 'Vendor', value: 'Dell Technologies' },
          { label: 'Uploaded', value: '2 hours ago' },
          { label: 'Parse confidence', value: '85%' },
          { label: 'Normalization', value: 'Editable' },
        ]}
      />
      <SecondaryTabs
        tabs={[
          { id: 'overview', label: 'Overview' },
          { id: 'parsed-line-items', label: 'Parsed Line Items', count: 24 },
        ]}
        activeTab={activeTab}
        onChange={setActiveTab}
      />
      {activeTab === 'overview' ? (
        <div className="grid gap-5 xl:grid-cols-[1.15fr,0.85fr]">
          <SectionCard title="Document preview" noPadBody>
            <DocPreview fileName="Dell_Quote_RFQ2401.pdf" pageInfo="Page 1 of 4" className="m-4 h-[360px]" />
          </SectionCard>
          <div className="space-y-5">
            <SectionCard title="Parse summary">
              <div className="space-y-3">
                <p className="text-sm font-medium text-slate-800">Dell Technologies</p>
                <p className="text-xs text-slate-500">vendor@dell.com</p>
                <ConfidenceBadge variant="high" showBar percentage={85} />
                <div className="rounded-md bg-slate-50 px-3 py-2 space-y-2">
                  <p className="text-[10px] font-semibold uppercase text-slate-400">Validation results</p>
                  {validationItems.map((item, i) => (
                    <div key={i} className="flex items-center gap-2 text-xs">
                      {item.pass ? (
                        <CheckCircle2 size={12} className="text-green-600 shrink-0" />
                      ) : (
                        <AlertTriangle size={12} className="text-amber-600 shrink-0" />
                      )}
                      <span className={item.pass ? 'text-slate-700' : 'text-amber-700'}>
                        {item.pass ? 'Pass' : (item as { warning?: string }).warning ?? 'Warning'}
                      </span>
                    </div>
                  ))}
                </div>
              </div>
            </SectionCard>
          </div>
        </div>
      ) : (
        <SectionCard title="Extracted line items" subtitle="Per-line mappings and overrides">
          <p className="text-sm text-slate-500">24 parsed lines. Use Normalize to map to RFQ line items.</p>
        </SectionCard>
      )}
      <div className="flex items-center gap-2 flex-wrap border-t border-slate-200 pt-4">
        <Button size="sm" variant="ghost">
          Reject
        </Button>
        <Button size="sm" variant="outline">
          Accept
        </Button>
        <Button
          size="sm"
          variant="primary"
          onClick={() => router.push(`/rfqs/${encodeURIComponent(rfqId)}/quote-intake/${encodeURIComponent(quoteId)}/normalize`)}
        >
          Accept & Normalize
        </Button>
        <Button size="sm" variant="ghost">Replace Document</Button>
        <Button size="sm" variant="ghost">Re-Parse</Button>
      </div>
    </div>
  );
}
