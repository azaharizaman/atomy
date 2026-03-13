'use client';

import React from 'react';
import { PageHeader } from '@/components/ds/FilterBar';
import { SectionCard, EmptyState } from '@/components/ds/Card';
import { Button } from '@/components/ds/Button';
import { StatusBadge } from '@/components/ds/Badge';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';
import { useRfq } from '@/hooks/use-rfq';
import { useRfqVendors } from '@/hooks/use-rfq-vendors';
import { Users, Mail } from 'lucide-react';

export default function RfqVendorsPage({ params }: { params: Promise<{ rfqId: string }> }) {
  const { rfqId } = React.use(params);
  const { data: rfq } = useRfq(rfqId);
  const { data: vendors = [], isLoading } = useRfqVendors(rfqId);

  const breadcrumbItems = [
    { label: 'RFQs', href: '/rfqs' },
    { label: rfq?.title ?? 'Requisition', href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
    { label: 'Vendors' },
  ];

  return (
    <div className="space-y-5">
      <WorkspaceBreadcrumbs items={breadcrumbItems} />
      <PageHeader
        title="Invited vendors"
        subtitle="Roster, invitation state, and quick outreach actions"
        actions={
          <Button size="sm" variant="outline">
            <Mail size={14} className="mr-1.5" />
            Invite vendors
          </Button>
        }
      />
      <SectionCard title="Vendor roster" subtitle={isLoading ? 'Loading…' : `${vendors.length} invited`}>
        {isLoading ? (
          <div className="flex items-center justify-center py-12">
            <div className="w-6 h-6 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin" />
          </div>
        ) : vendors.length === 0 ? (
          <EmptyState
            icon={<Users size={20} />}
            title="No vendors invited yet"
            description="Invite vendors to receive and compare quotes."
            action={<Button size="sm">Invite vendors</Button>}
          />
        ) : (
          <div className="space-y-3">
            {vendors.map((v) => (
              <div
                key={v.id}
                className="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-3"
              >
                <div>
                  <p className="text-sm font-medium text-slate-800">{v.name}</p>
                  <p className="text-xs text-slate-500">{v.contact}</p>
                </div>
                <StatusBadge
                  status={v.status === 'responded' ? 'approved' : 'pending'}
                  label={v.status === 'responded' ? 'Responded' : 'Invited'}
                />
              </div>
            ))}
          </div>
        )}
      </SectionCard>
    </div>
  );
}
