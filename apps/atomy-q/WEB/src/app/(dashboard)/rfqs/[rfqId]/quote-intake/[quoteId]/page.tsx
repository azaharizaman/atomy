'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import { SectionCard, DocPreview } from '@/components/ds/Card';
import { Button } from '@/components/ds/Button';
import { RecordHeader } from '@/components/ds/RecordHeader';
import { ConfidenceBadge } from '@/components/ds/Badge';
import { SecondaryTabs } from '@/components/ds/Tabs';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';
import { useAiStatus } from '@/hooks/use-ai-status';
import { useRfq } from '@/hooks/use-rfq';
import { useQuoteSubmission } from '@/hooks/use-quote-submission';
import { CheckCircle2, AlertTriangle } from 'lucide-react';

const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

export default function QuoteIntakeDetailPage({
  params,
}: {
  params: Promise<{ rfqId: string; quoteId: string }>;
}) {
  const router = useRouter();
  const { rfqId, quoteId } = React.use(params);
  const aiStatus = useAiStatus();
  const { data: rfq } = useRfq(rfqId);
  const { data: submission } = useQuoteSubmission(quoteId);
  const [activeTab, setActiveTab] = React.useState('overview');
  const blockingCount = submission?.blocking_issue_count ?? 0;
  const fileName = submission?.original_filename ?? 'Quote submission';
  const vendorName = submission?.vendor_name ?? 'Vendor';
  const confidenceValue = submission?.confidence ?? null;
  const confidenceVariant =
    confidenceValue === null || confidenceValue === undefined
      ? 'medium'
      : confidenceValue >= 90
        ? 'high'
        : confidenceValue >= 70
          ? 'medium'
          : 'low';
  const uploadedAt = submission?.submitted_at ?? null;
  const statusLabel = submission?.status === 'ready' ? 'Ready' : submission?.status === 'needs_review' ? 'Needs review' : submission?.status ?? 'Uploaded';
  const statusBadge = submission?.status === 'ready' ? 'approved' : submission?.status === 'needs_review' ? 'pending' : submission?.status === 'failed' ? 'error' : 'processing';
  const showExtractionUnavailable =
    !useMocks && aiStatus.shouldShowUnavailableMessage('quote_document_extraction');
  const hideExtractionControls = !useMocks && aiStatus.shouldHideAiControls('quote_document_extraction');

  const breadcrumbItems = [
    { label: 'RFQs', href: '/rfqs' },
    { label: rfq?.title ?? 'Requisition', href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
    { label: 'Quote Intake', href: `/rfqs/${encodeURIComponent(rfqId)}/quote-intake` },
    { label: vendorName },
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
      {!useMocks && blockingCount > 0 && (
        <div className="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-950">
          <span className="font-semibold">Blocking issues:</span> {blockingCount} — resolve in normalize before comparison freeze.
        </div>
      )}
      {showExtractionUnavailable && (
        <div className="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-950">
          <span className="font-semibold">AI extraction is unavailable.</span> Continue by entering source lines manually in Normalize.
        </div>
      )}
      <RecordHeader
        title={fileName}
        status={statusBadge}
        metadata={[
          { label: 'Vendor', value: vendorName },
          { label: 'Uploaded', value: uploadedAt ?? '—' },
          { label: 'Parse confidence', value: confidenceValue !== null && confidenceValue !== undefined ? `${Math.round(confidenceValue)}%` : '—' },
          { label: 'Normalization', value: statusLabel },
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
            <DocPreview fileName={fileName} pageInfo="Page 1 of 4" className="m-4 h-[360px]" />
          </SectionCard>
          <div className="space-y-5">
            <SectionCard title="Parse summary">
              <div className="space-y-3">
                <p className="text-sm font-medium text-slate-800">{vendorName}</p>
                <p className="text-xs text-slate-500">{submission?.vendor_id ?? '—'}</p>
                <ConfidenceBadge
                  variant={confidenceVariant}
                  showBar
                  percentage={typeof confidenceValue === 'number' ? confidenceValue : undefined}
                />
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
        {!hideExtractionControls && <Button size="sm" variant="ghost">Re-Parse</Button>}
      </div>
    </div>
  );
}
