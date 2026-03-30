'use client';

import React from 'react';
import { Send } from 'lucide-react';
import { Button } from '@/components/ds/Button';
import { SectionCard } from '@/components/ds/Card';
import { StatusBadge } from '@/components/ds/Badge';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';
import { useAward, useAwardRfq } from '@/hooks/use-award';
import { useRfq } from '@/hooks/use-rfq';
import { useRfqOverview } from '@/hooks/use-rfq-overview';
import { useRfqVendors } from '@/hooks/use-rfq-vendors';

export function AwardPageContent({ rfqId }: { rfqId: string }) {
  const { data: rfq } = useRfq(rfqId);
  const { data: overview } = useRfqOverview(rfqId);
  const { data: award } = useAward(rfqId);
  const { data: vendors = [] } = useRfqVendors(rfqId);
  const awardWorkflow = useAwardRfq();
  const [selectedVendorId, setSelectedVendorId] = React.useState('');

  React.useEffect(() => {
    if (selectedVendorId !== '' || vendors.length === 0) {
      return;
    }

    setSelectedVendorId(vendors[0].id);
  }, [selectedVendorId, vendors]);

  const comparisonRunId = overview?.comparison?.id ?? '';
  const awardVendorId = award?.vendor_id ?? award?.winner_vendor_id ?? '';
  const awardVendorName = award?.vendor_name ?? award?.winner_vendor_name ?? 'Winner Vendor';
  const nonWinners = awardVendorId
    ? vendors.filter((vendor) => vendor.id !== awardVendorId)
    : vendors.slice(1);

  const nonAwardedLabel = comparisonRunId
    ? 'Select the vendor to record the workflow award. Amount and sign-off fields are derived automatically.'
    : 'A finalized comparison run is required before an award can be recorded.';

  const canAward = Boolean(comparisonRunId && selectedVendorId && !awardWorkflow.isPending);

  const handleAward = () => {
    if (!canAward) {
      return;
    }

    awardWorkflow.mutate({
      rfq_id: rfqId,
      comparison_run_id: comparisonRunId,
      vendor_id: selectedVendorId,
    });
  };

  const breadcrumbItems = [
    { label: 'RFQs', href: '/rfqs' },
    { label: rfq?.title ?? 'Requisition', href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
    { label: 'Award' },
  ];

  return (
    <div className="space-y-5">
      <WorkspaceBreadcrumbs items={breadcrumbItems} />
      <div className="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 flex items-center gap-2">
        <StatusBadge status={award ? 'approved' : 'pending'} label={award ? 'Awarded' : 'Awaiting Award'} />
        <span className="text-sm text-slate-700">
          {award
            ? `Awarded to ${awardVendorName}. Total: $${award.amount.toLocaleString()} · Savings: ${award.savings_percentage?.toFixed(2) ?? '0.00'}%`
            : nonAwardedLabel}
        </span>
      </div>
      <div className="grid gap-5 xl:grid-cols-[1fr,400px]">
        <div className="space-y-5">
          <SectionCard title={award ? 'Awarded winner' : 'Recommended workflow award'}>
            <div className="space-y-2">
              <p className="text-sm font-semibold text-slate-900">{award ? awardVendorName : selectedVendorId ? vendors.find((vendor) => vendor.id === selectedVendorId)?.name ?? 'Winner Vendor' : 'Winner Vendor'}</p>
              <p className="text-xs text-slate-600">
                {award
                  ? `Total: $${award.amount.toLocaleString()} · Savings: ${award.savings_percentage?.toFixed(2) ?? '0.00'}%`
                  : 'The workflow records the award amount and sign-off fields automatically.'}
              </p>
              <StatusBadge status={award ? 'approved' : 'pending'} label={award ? 'Awarded' : 'Pending award'} />
            </div>
          </SectionCard>
          {!award ? (
            <SectionCard title="Finalize award">
              <div className="space-y-3">
                <div>
                  <label htmlFor="award-winner-vendor" className="block text-sm font-medium text-slate-700">
                    Winner vendor
                  </label>
                  <select
                    id="award-winner-vendor"
                    className="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm"
                    value={selectedVendorId}
                    onChange={(event) => setSelectedVendorId(event.target.value)}
                  >
                    {vendors.map((vendor) => (
                      <option key={vendor.id} value={vendor.id}>
                        {vendor.name}
                      </option>
                    ))}
                  </select>
                </div>
                <p className="text-xs text-slate-500">{nonAwardedLabel}</p>
                <Button size="sm" variant="primary" onClick={handleAward} disabled={!canAward}>
                  {awardWorkflow.isPending ? 'Recording award...' : 'Award vendor'}
                </Button>
              </div>
            </SectionCard>
          ) : (
            <SectionCard title="Award sign-off">
              <div className="space-y-3">
                <label className="flex items-center gap-2 text-sm text-slate-700">
                  <input type="checkbox" className="rounded border-slate-300" checked readOnly />
                  Financial review complete
                </label>
                <label className="flex items-center gap-2 text-sm text-slate-700">
                  <input type="checkbox" className="rounded border-slate-300" checked readOnly />
                  Legal review complete
                </label>
                <label className="flex items-center gap-2 text-sm text-slate-700">
                  <input type="checkbox" className="rounded border-slate-300" checked readOnly />
                  Procurement lead sign-off
                </label>
                <Button size="sm" variant="primary" disabled>
                  Finalize Award
                </Button>
              </div>
            </SectionCard>
          )}
        </div>
        <SectionCard title="Vendor debrief">
          <p className="text-xs text-slate-500 mb-3">Non-winning vendors</p>
          <div className="space-y-2">
            {nonWinners.map((vendor) => (
              <div key={vendor.id} className="flex items-center justify-between gap-2">
                <span className="text-sm text-slate-700">{vendor.name}</span>
                <Button size="xs" variant="ghost" disabled>
                  <Send size={12} className="mr-1" />
                  Send debrief
                </Button>
              </div>
            ))}
            {nonWinners.length === 0 ? (
              <p className="text-sm text-slate-500">No non-winning vendors yet.</p>
            ) : null}
          </div>
        </SectionCard>
      </div>
    </div>
  );
}

export default function RfqAwardPage({ params }: { params: Promise<{ rfqId: string }> }) {
  const { rfqId } = React.use(params);

  return <AwardPageContent rfqId={rfqId} />;
}
