'use client';

import React from 'react';
import { SectionCard, Card } from '@/components/ds/Card';
import { Button } from '@/components/ds/Button';
import { StatusBadge } from '@/components/ds/Badge';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';
import { useRfq } from '@/hooks/use-rfq';
import { Lock, Unlock } from 'lucide-react';

const MOCK_SOURCE_LINES = [
  { id: '1', lineNo: 1, description: 'PowerEdge R750 Server', qty: 12, unit: 'units', unitPrice: 4200, confidence: 'high' as const, conflict: false },
  { id: '2', lineNo: 2, description: 'PowerEdge R650 Server', qty: 6, unit: 'units', unitPrice: 3100, confidence: 'medium' as const, conflict: true },
  { id: '3', lineNo: 3, description: 'SAN Array 50TB', qty: 2, unit: 'units', unitPrice: 28500, confidence: 'high' as const, conflict: false },
];

export default function NormalizePage({
  params,
}: {
  params: Promise<{ rfqId: string; quoteId: string }>;
}) {
  const { rfqId } = React.use(params);
  const { data: rfq } = useRfq(rfqId);
  const [locked, setLocked] = React.useState(false);

  const breadcrumbItems = [
    { label: 'RFQs', href: '/rfqs' },
    { label: rfq?.title ?? 'Requisition', href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
    { label: 'Quote Intake', href: `/rfqs/${encodeURIComponent(rfqId)}/quote-intake` },
    { label: 'Dell Technologies', href: `/rfqs/${encodeURIComponent(rfqId)}/quote-intake/quoteId` },
    { label: 'Normalize' },
  ];

  return (
    <div className="space-y-5">
      <WorkspaceBreadcrumbs items={breadcrumbItems} />
      <div className="flex items-center gap-3 flex-wrap">
        <Button size="sm" variant="outline" disabled>
          Bulk Apply Mapping
        </Button>
        <Button size="sm" variant="outline" onClick={() => setLocked(!locked)}>
          {locked ? <Lock size={14} className="mr-1.5" /> : <Unlock size={14} className="mr-1.5" />}
          {locked ? 'Unlock' : 'Lock for Comparison'}
        </Button>
        <span className="text-xs text-slate-500">
          {locked ? 'Locked' : 'Unlocked'}
        </span>
        <StatusBadge status="draft" label="Fully Normalized: 18/24 lines" />
      </div>
      <div className="grid gap-5 xl:grid-cols-[0.45fr,0.55fr]">
        <SectionCard title="Source lines" subtitle="Extracted vendor line items">
          <div className="space-y-2">
            {MOCK_SOURCE_LINES.map((line) => (
              <div
                key={line.id}
                className="flex items-center gap-3 rounded border border-slate-200 px-3 py-2 text-xs"
              >
                <input type="checkbox" className="rounded border-slate-300" />
                <span className="w-6 text-slate-500">{line.lineNo}</span>
                <span className="flex-1 truncate text-slate-800">{line.description}</span>
                <span className="text-slate-500">{line.qty} {line.unit}</span>
                <span className="font-medium tabular-nums">${line.unitPrice.toLocaleString()}</span>
                <StatusBadge status={line.conflict ? 'pending' : 'approved'} size="xs" label={line.confidence} />
              </div>
            ))}
          </div>
        </SectionCard>
        <SectionCard title="Normalized mapping" subtitle="Map to RFQ line items">
          <div className="space-y-2 text-xs">
            {MOCK_SOURCE_LINES.map((line) => (
              <div
                key={line.id}
                className="grid grid-cols-5 gap-2 rounded border border-slate-200 px-3 py-2 items-center"
              >
                <span className="col-span-2 truncate text-slate-800">RFQ Line #{line.lineNo}</span>
                <span className="text-slate-500 font-mono">43211500</span>
                <span>{line.qty} {line.unit}</span>
                <span className="tabular-nums font-medium">${line.unitPrice.toLocaleString()}</span>
              </div>
            ))}
          </div>
        </SectionCard>
      </div>
    </div>
  );
}
