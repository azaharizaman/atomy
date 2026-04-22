'use client';

import React from 'react';
import Link from 'next/link';
import { AlertTriangle, CheckCircle2, Mail, Search, Users } from 'lucide-react';
import { StatusBadge } from '@/components/ds/Badge';
import { Button } from '@/components/ds/Button';
import { EmptyState, SectionCard } from '@/components/ds/Card';
import { PageHeader } from '@/components/ds/FilterBar';
import { useRequisitionVendorSelection } from '@/hooks/use-requisition-vendor-selection';
import { useRfq } from '@/hooks/use-rfq';
import { useRfqVendors } from '@/hooks/use-rfq-vendors';
import { useUpdateRequisitionVendorSelection } from '@/hooks/use-update-requisition-vendor-selection';
import { useVendors, type VendorRow } from '@/hooks/use-vendors';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';

function vendorLabel(vendor: VendorRow): string {
  return vendor.displayName || vendor.legalName || vendor.name || 'Unknown vendor';
}

function vendorEmail(vendor: VendorRow): string {
  return vendor.primaryContactEmail || vendor.email || 'No contact email';
}

export function RfqVendorSelectionPanel({ rfqId }: { rfqId: string }) {
  const [search, setSearch] = React.useState('');
  const vendorsQuery = useVendors({ status: 'approved', q: search });
  const selectedQuery = useRequisitionVendorSelection(rfqId);
  const updateSelection = useUpdateRequisitionVendorSelection(rfqId);
  const approvedVendors = (vendorsQuery.data?.items ?? []).filter((vendor) => vendor.status === 'approved');
  const selectedVendorIds = React.useMemo(
    () => new Set((selectedQuery.data ?? []).map((row) => row.vendorId)),
    [selectedQuery.data],
  );
  const [draftSelected, setDraftSelected] = React.useState<Set<string>>(selectedVendorIds);
  const [isDirty, setIsDirty] = React.useState(false);

  React.useEffect(() => {
    if (isDirty) {
      return;
    }

    setDraftSelected(new Set(selectedVendorIds));
  }, [isDirty, selectedVendorIds]);

  const toggleVendor = (vendorId: string) => {
    setIsDirty(true);
    setDraftSelected((current) => {
      const next = new Set(current);
      if (next.has(vendorId)) {
        next.delete(vendorId);
      } else {
        next.add(vendorId);
      }
      return next;
    });
  };

  const saveSelection = async () => {
    await updateSelection.mutateAsync({ vendor_ids: Array.from(draftSelected) });
    setIsDirty(false);
  };

  const isLoading = vendorsQuery.isLoading || selectedQuery.isLoading;
  const hasError = vendorsQuery.isError || selectedQuery.isError;
  const error = vendorsQuery.error ?? selectedQuery.error;

  return (
    <SectionCard
      title="Approved vendor selection"
      subtitle={`${draftSelected.size} selected for this requisition`}
      actions={
        <Button size="sm" onClick={saveSelection} loading={updateSelection.isPending} disabled={draftSelected.size === 0}>
          Save selection
        </Button>
      }
    >
      <div className="space-y-4">
        <label className="block">
          <span className="sr-only">Search approved vendors</span>
          <span className="flex h-9 items-center gap-2 rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-500">
            <Search size={15} />
            <input
              type="search"
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              placeholder="Search approved vendors"
              aria-label="Search approved vendors"
              className="min-w-0 flex-1 border-0 bg-transparent text-sm text-slate-800 outline-none placeholder:text-slate-400"
            />
          </span>
        </label>

        {hasError ? (
          <EmptyState
            icon={<AlertTriangle size={20} />}
            title="Could not load approved vendors"
            description={error instanceof Error ? error.message : 'Approved vendor selection is unavailable.'}
          />
        ) : isLoading ? (
          <div className="flex items-center justify-center py-10">
            <div className="h-6 w-6 animate-spin rounded-full border-2 border-indigo-500 border-t-transparent" />
          </div>
        ) : approvedVendors.length === 0 ? (
          <EmptyState
            icon={<Users size={20} />}
            title="No approved vendors available"
            description="Create or approve vendors in the vendor master before adding them to this requisition."
            action={
              <Link
                href="/vendors"
                className="inline-flex h-7 items-center justify-center rounded border border-slate-300 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50"
              >
                Browse vendors
              </Link>
            }
          />
        ) : (
          <div className="space-y-2">
            {approvedVendors.map((vendor) => {
              const checked = draftSelected.has(vendor.id);
              return (
                <label
                  key={vendor.id}
                  className="flex cursor-pointer items-center justify-between gap-4 rounded-md border border-slate-200 px-4 py-3 hover:bg-slate-50"
                >
                  <span className="flex min-w-0 items-center gap-3">
                    <input
                      type="checkbox"
                      checked={checked}
                      onChange={() => toggleVendor(vendor.id)}
                      aria-label={`Select ${vendorLabel(vendor)}`}
                      className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    <span className="min-w-0">
                      <span className="block truncate text-sm font-medium text-slate-800">{vendorLabel(vendor)}</span>
                      <span className="block truncate text-xs text-slate-500">{vendorEmail(vendor)}</span>
                    </span>
                  </span>
                  {checked ? <CheckCircle2 size={16} className="shrink-0 text-emerald-600" /> : null}
                </label>
              );
            })}
          </div>
        )}

        {updateSelection.isError ? (
          <p className="text-sm text-red-600">
            {updateSelection.error instanceof Error ? updateSelection.error.message : 'Vendor selection could not be saved.'}
          </p>
        ) : null}
      </div>
    </SectionCard>
  );
}

export function RfqVendorsPageContent({ rfqId }: { rfqId: string }) {
  const rfqQuery = useRfq(rfqId);
  const vendorsQuery = useRfqVendors(rfqId);
  const selectedQuery = useRequisitionVendorSelection(rfqId);
  const rfq = rfqQuery.data;
  const vendors = vendorsQuery.data ?? [];
  const selectedVendors = selectedQuery.data ?? [];
  const isLoading = vendorsQuery.isLoading;
  const inviteDisabled = selectedQuery.isError || selectedVendors.length === 0;
  const selectedSubtitle = selectedQuery.isLoading ? 'Loading...' : `${selectedVendors.length} ready for invitation`;

  const breadcrumbItems = [
    { label: 'RFQs', href: '/rfqs' },
    { label: rfq?.title ?? 'Requisition', href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
    { label: 'Vendors' },
  ];

  if (rfqQuery.isError) {
    const errorMessage = rfqQuery.error instanceof Error ? rfqQuery.error.message : 'RFQ data is unavailable.';
    return (
      <div className="space-y-5">
        <WorkspaceBreadcrumbs items={breadcrumbItems} />
        <PageHeader title="Invited vendors" subtitle="RFQ unavailable" />
        <SectionCard title="RFQ unavailable">
          <EmptyState icon={<AlertTriangle size={20} />} title="Could not load RFQ context" description={errorMessage} />
        </SectionCard>
      </div>
    );
  }

  if (vendorsQuery.isError) {
    const errorMessage =
      vendorsQuery.error instanceof Error ? vendorsQuery.error.message : 'The live vendor roster could not be loaded.';
    return (
      <div className="space-y-5">
        <WorkspaceBreadcrumbs items={breadcrumbItems} />
        <PageHeader
          title="Invited vendors"
          subtitle="Vendor roster unavailable"
          actions={
            <Button size="sm" variant="outline" disabled>
              <Mail size={14} className="mr-1.5" />
              Invite vendors
            </Button>
          }
        />
        <SectionCard title="Vendor roster unavailable">
          <EmptyState icon={<Users size={20} />} title="Could not load invited vendors" description={errorMessage} />
        </SectionCard>
      </div>
    );
  }

  return (
    <div className="space-y-5">
      <WorkspaceBreadcrumbs items={breadcrumbItems} />
      <PageHeader
        title="Invited vendors"
        subtitle="Selected approved vendors, invitation state, and quick outreach actions"
        actions={
          <Button size="sm" variant="outline" disabled={inviteDisabled}>
            <Mail size={14} className="mr-1.5" />
            Invite vendors
          </Button>
        }
      />
      {inviteDisabled ? (
        <p className="text-sm text-slate-500">Select approved vendors to enable invitations.</p>
      ) : null}
      <RfqVendorSelectionPanel rfqId={rfqId} />
      <SectionCard title="Selected approved vendors" subtitle={selectedSubtitle}>
        {selectedQuery.isLoading ? (
          <div className="flex items-center justify-center py-10">
            <div className="h-6 w-6 animate-spin rounded-full border-2 border-indigo-500 border-t-transparent" />
          </div>
        ) : selectedQuery.isError ? (
          <EmptyState
            icon={<AlertTriangle size={20} />}
            title="Could not load selected vendors"
            description={
              selectedQuery.error instanceof Error
                ? selectedQuery.error.message
                : 'The selected vendor list could not be loaded.'
            }
          />
        ) : selectedVendors.length === 0 ? (
          <EmptyState
            icon={<Users size={20} />}
            title="No approved vendors selected"
            description="Confirm approved vendor selections before inviting suppliers into this RFQ."
          />
        ) : (
          <div className="space-y-2">
            {selectedVendors.map((vendor) => (
              <div key={vendor.id} className="flex items-center justify-between gap-4 rounded-md border border-slate-200 px-4 py-3">
                <div className="min-w-0">
                  <p className="truncate text-sm font-medium text-slate-800">{vendor.vendorName}</p>
                  <p className="truncate text-xs text-slate-500">{vendor.vendorEmail}</p>
                </div>
                <StatusBadge status="approved" label="Selected" />
              </div>
            ))}
          </div>
        )}
      </SectionCard>
      <SectionCard title="Vendor roster" subtitle={isLoading ? 'Loading...' : `${vendors.length} invited`}>
        {isLoading ? (
          <div className="flex items-center justify-center py-12">
            <div className="h-6 w-6 animate-spin rounded-full border-2 border-indigo-500 border-t-transparent" />
          </div>
        ) : vendors.length === 0 ? (
          <EmptyState
            icon={<Users size={20} />}
            title="No vendors invited yet"
            description="Invitations are sent only to approved vendors confirmed in the selection list."
            action={<Button size="sm" disabled={inviteDisabled}>Invite vendors</Button>}
          />
        ) : (
          <div className="space-y-3">
            {vendors.map((v) => (
              <div key={v.id} className="flex items-center justify-between gap-4 rounded-md border border-slate-200 px-4 py-3">
                <div className="min-w-0">
                  <p className="truncate text-sm font-medium text-slate-800">{v.name}</p>
                  <p className="truncate text-xs text-slate-500">{v.contact}</p>
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

export default function RfqVendorsPage({ params }: { params: Promise<{ rfqId: string }> }) {
  const { rfqId } = React.use(params);
  return <RfqVendorsPageContent rfqId={rfqId} />;
}
