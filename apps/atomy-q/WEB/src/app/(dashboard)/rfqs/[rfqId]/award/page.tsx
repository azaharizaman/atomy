'use client';

import React from 'react';
import { Send } from 'lucide-react';
import { useRouter } from 'next/navigation';
import { Button } from '@/components/ds/Button';
import { Card, SectionCard } from '@/components/ds/Card';
import { StatusBadge } from '@/components/ds/Badge';
import { PageHeader } from '@/components/ds/FilterBar';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';
import { useAward } from '@/hooks/use-award';
import { useRfq } from '@/hooks/use-rfq';

const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

function formatAmount(amount: number | null | undefined, currency: string | null | undefined): string {
  if (amount === null || amount === undefined) return '—';
  const ccy = currency ?? 'USD';
  try {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: ccy }).format(amount);
  } catch {
    return `$${amount.toLocaleString()}`;
  }
}

function awardBadge(status: string | null | undefined): { status: 'pending' | 'approved' | 'rejected'; label: string } {
  if (status === 'signed_off') {
    return { status: 'approved', label: 'Signed off' };
  }
  if (status === 'protested') {
    return { status: 'rejected', label: 'Protested' };
  }
  return { status: 'pending', label: 'Pending' };
}

export function RfqAwardPageContent({ rfqId }: { rfqId: string }) {
  const router = useRouter();
  const { data: rfq } = useRfq(rfqId);
  const { award, debrief, signoff } = useAward(rfqId);
  const [debriefMessage, setDebriefMessage] = React.useState('');
  const displayAward = award ?? null;
  const awardStatus = awardBadge(displayAward?.status);
  const isFinalized = awardStatus.status === 'approved';
  const nonWinners = displayAward?.comparison?.vendors?.length
    ? displayAward.comparison.vendors.filter((vendor) => vendor.vendor_id !== '' && vendor.vendor_id !== displayAward.vendor_id)
    : [];
  const awardAmount = formatAmount(displayAward?.amount ?? null, displayAward?.currency ?? null);

  const breadcrumbItems = [
    { label: 'RFQs', href: '/rfqs' },
    { label: rfq?.title ?? 'Requisition', href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
    { label: 'Award' },
  ];

  return (
    <div className="space-y-5">
      <WorkspaceBreadcrumbs items={breadcrumbItems} />
      <PageHeader
        title="Award"
        subtitle={
          displayAward
            ? `${isFinalized ? 'Award for' : 'Recommended winner for'} ${displayAward.rfq_number ?? rfq?.rfq_number ?? rfqId}`
            : 'No award record yet'
        }
        actions={
          <Button size="sm" variant="outline" onClick={() => router.push(`/rfqs/${encodeURIComponent(rfqId)}/comparison-runs`)}>
            Comparison runs
          </Button>
        }
      />
      <div className="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 flex items-center gap-2">
        <StatusBadge status={awardStatus.status} label={awardStatus.label} />
        <span className="text-sm text-slate-700">
          {displayAward
            ? `${isFinalized ? 'Awarded to' : 'Recommended for'} ${displayAward.vendor_name ?? 'Unknown vendor'} · ${isFinalized ? 'Total' : 'Proposed total'}: ${awardAmount}`
            : 'No award has been created for this RFQ yet.'}
        </span>
      </div>
      <div className="grid gap-5 xl:grid-cols-[1fr,400px]">
        <div className="space-y-5">
          <SectionCard title={displayAward ? (isFinalized ? 'Awarded winner' : 'Recommended winner') : 'Recommended winner'}>
            <div className="space-y-2">
              <p className="text-sm font-semibold text-slate-900">{displayAward?.vendor_name ?? 'No award selected'}</p>
              <p className="text-xs text-slate-600">
                {displayAward
                  ? `${isFinalized ? 'Amount' : 'Proposed amount'}: ${awardAmount}`
                  : 'Freeze a comparison run to create an award record.'}
              </p>
              <StatusBadge status={awardStatus.status} label={displayAward ? (isFinalized ? awardStatus.label : 'Recommended') : 'Recommended'} />
            </div>
          </SectionCard>
          <SectionCard title="Sign-off">
            <div className="space-y-3">
              <Button
                size="sm"
                variant="primary"
                disabled={!displayAward || displayAward.status === 'signed_off' || signoff.isPending || useMocks}
                onClick={() => {
                  if (displayAward) {
                    signoff.mutate(displayAward.id);
                  }
                }}
              >
                Finalize Award
              </Button>
              <p className="text-xs text-slate-500">
                {displayAward?.signoff_at ? `Signed off at ${displayAward.signoff_at}` : 'No sign-off recorded yet.'}
              </p>
            </div>
          </SectionCard>
        </div>
        <Card className="space-y-3">
          <div className="space-y-2">
            <p className="text-xs text-slate-500">Non-winning vendors</p>
            <label className="block space-y-1">
              <span className="text-xs font-medium text-slate-600">Debrief message</span>
              <textarea
                className="min-h-24 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                value={debriefMessage}
                onChange={(event) => setDebriefMessage(event.target.value)}
                placeholder="Add the message that will be sent to non-winning vendors."
              />
            </label>
            <p className="text-[11px] text-slate-500">
              The same message is sent to each selected non-winning vendor.
            </p>
          </div>
          <div className="space-y-2">
            {nonWinners.length > 0 ? (
              nonWinners.map((vendor) => (
                <div key={vendor.vendor_id} className="flex items-center justify-between gap-2">
                  <span className="text-sm text-slate-700">{vendor.vendor_name ?? vendor.vendor_id}</span>
                  <Button
                    size="xs"
                    variant="ghost"
                    disabled={
                      !displayAward ||
                      vendor.vendor_id === '' ||
                      debrief.isPending ||
                      useMocks ||
                      debriefMessage.trim() === ''
                    }
                    onClick={() => {
                      const message = debriefMessage.trim();
                      if (displayAward && vendor.vendor_id && message !== '') {
                        debrief.mutate({
                          awardId: displayAward.id,
                          vendorId: vendor.vendor_id,
                          message,
                        });
                      }
                    }}
                  >
                    <Send size={12} className="mr-1" />
                    Send debrief
                  </Button>
                </div>
              ))
            ) : (
              <p className="text-sm text-slate-500">
                {displayAward
                  ? 'No non-winning vendors were found in the frozen comparison snapshot.'
                  : 'Create an award to reveal non-winning vendors.'}
              </p>
            )}
          </div>
        </Card>
      </div>
    </div>
  );
}

export default function RfqAwardPage({ params }: { params: Promise<{ rfqId: string }> }) {
  const { rfqId } = React.use(params);
  return <RfqAwardPageContent rfqId={rfqId} />;
}
