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
import { useRfqVendors } from '@/hooks/use-rfq-vendors';

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

export function RfqAwardPageContent({ rfqId }: { rfqId: string }) {
  const router = useRouter();
  const { data: rfq } = useRfq(rfqId);
  const { award, debrief, signoff } = useAward(rfqId);
  const { data: vendors = [] } = useRfqVendors(rfqId);
  const displayAward = award ?? null;
  const nonWinners = displayAward ? vendors.filter((vendor) => vendor.id !== displayAward.vendor_id) : vendors;
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
        subtitle={displayAward ? `Award for ${displayAward.rfq_number ?? rfq?.rfq_number ?? rfqId}` : 'No award record yet'}
        actions={
          <Button size="sm" variant="outline" onClick={() => router.push(`/rfqs/${encodeURIComponent(rfqId)}/comparison-runs`)}>
            Comparison runs
          </Button>
        }
      />
      <div className="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 flex items-center gap-2">
        <StatusBadge status={displayAward?.status === 'signed_off' ? 'approved' : 'pending'} label={displayAward?.status === 'signed_off' ? 'Signed off' : 'Pending'} />
        <span className="text-sm text-slate-700">
          {displayAward
            ? `Awarded to ${displayAward.vendor_name ?? 'Unknown vendor'} · Total: ${awardAmount}`
            : 'No award has been created for this RFQ yet.'}
        </span>
      </div>
      <div className="grid gap-5 xl:grid-cols-[1fr,400px]">
        <div className="space-y-5">
          <SectionCard title={displayAward ? 'Awarded winner' : 'Recommended winner'}>
            <div className="space-y-2">
              <p className="text-sm font-semibold text-slate-900">{displayAward?.vendor_name ?? 'No award selected'}</p>
              <p className="text-xs text-slate-600">
                {displayAward
                  ? `Amount: ${awardAmount}`
                  : 'Freeze a comparison run to create an award record.'}
              </p>
              <StatusBadge status={displayAward ? 'approved' : 'pending'} label={displayAward ? 'Awarded' : 'Recommended'} />
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
          <p className="text-xs text-slate-500">Non-winning vendors</p>
          <div className="space-y-2">
            {nonWinners.length > 0 ? (
              nonWinners.map((vendor) => (
                <div key={vendor.id} className="flex items-center justify-between gap-2">
                  <span className="text-sm text-slate-700">{vendor.name}</span>
                  <Button
                    size="xs"
                    variant="ghost"
                    disabled={!displayAward || debrief.isPending || useMocks}
                    onClick={() => {
                      if (displayAward) {
                        debrief.mutate({
                          awardId: displayAward.id,
                          vendorId: vendor.id,
                          message: `Debrief for ${vendor.name}`,
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
              <p className="text-sm text-slate-500">Non-winning vendors are resolved from the frozen comparison snapshot.</p>
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
