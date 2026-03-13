'use client';

import React from 'react';
import { PageHeader } from '@/components/ds/FilterBar';
import { SectionCard, Card } from '@/components/ds/Card';
import { Button } from '@/components/ds/Button';
import { StatusBadge } from '@/components/ds/Badge';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';
import { useRfq } from '@/hooks/use-rfq';
import { getSeedAwardByRfqId, getSeedVendorsByRfqId } from '@/data/seed';
import { CheckCircle2, Send } from 'lucide-react';

const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

export default function RfqAwardPage({ params }: { params: Promise<{ rfqId: string }> }) {
  const { rfqId } = React.use(params);
  const { data: rfq } = useRfq(rfqId);
  const award = useMocks ? getSeedAwardByRfqId(rfqId) : null;
  const vendors = useMocks ? getSeedVendorsByRfqId(rfqId) : [];
  const nonWinners = award ? vendors.filter((v) => v.id !== award.winnerVendorId).map((v) => v.name) : ['HP Enterprise', 'Lenovo', 'Cisco', 'IBM'];

  const breadcrumbItems = [
    { label: 'RFQs', href: '/rfqs' },
    { label: rfq?.title ?? 'Requisition', href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
    { label: 'Award' },
  ];

  return (
    <div className="space-y-5">
      <WorkspaceBreadcrumbs items={breadcrumbItems} />
      <div className="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 flex items-center gap-2">
        <StatusBadge status={award ? 'approved' : 'pending'} label={award ? 'Awarded' : 'Awaiting Sign-off'} />
        <span className="text-sm text-slate-700">
          {award
            ? `Awarded to ${award.winnerVendorName}. Total: $${award.amount.toLocaleString()} · Savings: ${award.savingsPercent}%`
            : 'Based on Comparison Run, approved.'}
        </span>
      </div>
      <div className="grid gap-5 xl:grid-cols-[1fr,400px]">
        <div className="space-y-5">
          <SectionCard title={award ? 'Awarded winner' : 'Recommended winner'}>
            <div className="space-y-2">
              <p className="text-sm font-semibold text-slate-900">{award?.winnerVendorName ?? 'Dell Technologies'}</p>
              <p className="text-xs text-slate-600">
                {award
                  ? `Total: $${award.amount.toLocaleString()} · Savings: ${award.savingsPercent}%`
                  : 'Total: $1,043,250 · Savings: $156,487 (13.1%) · Confidence: 82%'}
              </p>
              <StatusBadge status="approved" label={award ? 'Awarded' : 'Recommended'} />
            </div>
          </SectionCard>
          <SectionCard title="Sign-off">
            <div className="space-y-3">
              <label className="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" className="rounded border-slate-300" />
                Financial review complete
              </label>
              <label className="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" className="rounded border-slate-300" />
                Legal review complete
              </label>
              <label className="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" className="rounded border-slate-300" />
                Procurement lead sign-off
              </label>
              <Button size="sm" variant="primary" disabled>
                Finalize Award
              </Button>
            </div>
          </SectionCard>
        </div>
        <SectionCard title="Vendor debrief">
          <p className="text-xs text-slate-500 mb-3">Non-winning vendors</p>
          <div className="space-y-2">
            {nonWinners.map((name) => (
              <div key={name} className="flex items-center justify-between gap-2">
                <span className="text-sm text-slate-700">{name}</span>
                <Button size="xs" variant="ghost">
                  <Send size={12} className="mr-1" />
                  Send debrief
                </Button>
              </div>
            ))}
          </div>
        </SectionCard>
      </div>
    </div>
  );
}
