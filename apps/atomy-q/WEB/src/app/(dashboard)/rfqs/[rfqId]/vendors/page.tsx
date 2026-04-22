'use client';

import React from 'react';
import Link from 'next/link';
import { AlertTriangle, CheckCircle2, Info, Mail, Search, Users } from 'lucide-react';
import { StatusBadge } from '@/components/ds/Badge';
import { Button } from '@/components/ds/Button';
import { EmptyState, SectionCard } from '@/components/ds/Card';
import { PageHeader } from '@/components/ds/FilterBar';
import { useInviteSelectedVendors } from '@/hooks/use-invite-selected-vendors';
import { useRequisitionVendorSelection } from '@/hooks/use-requisition-vendor-selection';
import { useRfq } from '@/hooks/use-rfq';
import { useRfqVendors } from '@/hooks/use-rfq-vendors';
import { useUpdateRequisitionVendorSelection } from '@/hooks/use-update-requisition-vendor-selection';
import { useVendorRecommendations, type VendorRecommendationCandidate } from '@/hooks/use-vendor-recommendations';
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
  const [expandedRecommendationId, setExpandedRecommendationId] = React.useState<string | null>(null);
  const vendorsQuery = useVendors({ status: 'approved', q: search });
  const selectedQuery = useRequisitionVendorSelection(rfqId);
  const recommendationsQuery = useVendorRecommendations(rfqId);
  const updateSelection = useUpdateRequisitionVendorSelection(rfqId);
  const approvedVendors = (vendorsQuery.data?.items ?? []).filter((vendor) => vendor.status === 'approved');
  const selectedVendorIds = React.useMemo(
    () => new Set((selectedQuery.data ?? []).map((row) => row.vendorId)),
    [selectedQuery.data],
  );
  const recommendedById = React.useMemo(() => {
    const recommendations = new Map<string, VendorRecommendationCandidate>();
    for (const candidate of recommendationsQuery.data?.candidates ?? []) {
      recommendations.set(candidate.vendorId, candidate);
    }

    return recommendations;
  }, [recommendationsQuery.data]);
  const [draftSelected, setDraftSelected] = React.useState<Set<string>>(selectedVendorIds);
  const [isDirty, setIsDirty] = React.useState(false);

  React.useEffect(() => {
    if (isDirty) {
      return;
    }

    if (selectedVendorIds.size > 0 || recommendationsQuery.isLoading) {
      setDraftSelected(new Set(selectedVendorIds));
      return;
    }

    const recommendedIds = (recommendationsQuery.data?.candidates ?? []).map((candidate) => candidate.vendorId);
    if (recommendedIds.length > 0) {
      setDraftSelected(new Set(recommendedIds));
      return;
    }

    setDraftSelected(new Set());
  }, [isDirty, recommendationsQuery.data, recommendationsQuery.isLoading, selectedVendorIds]);

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
    try {
      await updateSelection.mutateAsync({ vendor_ids: Array.from(draftSelected) });
      setIsDirty(false);
    } catch {
      // Error state is exposed by the mutation; keep the draft intact for retry.
    }
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
              const recommendation = recommendedById.get(vendor.id);
              const expanded = expandedRecommendationId === vendor.id;
              return (
                <div key={vendor.id} className="rounded-md border border-slate-200 hover:bg-slate-50">
                  <label className="flex cursor-pointer items-center justify-between gap-4 px-4 py-3">
                    <span className="flex min-w-0 items-center gap-3">
                      <input
                        type="checkbox"
                        checked={checked}
                        onChange={() => toggleVendor(vendor.id)}
                        aria-label={`Select ${vendorLabel(vendor)}`}
                        className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                      />
                      <span className="min-w-0">
                        <span className="flex min-w-0 items-center gap-2">
                          <span className="truncate text-sm font-medium text-slate-800">{vendorLabel(vendor)}</span>
                          {recommendation ? (
                            <span className="shrink-0 rounded border border-emerald-200 bg-emerald-50 px-1.5 py-0.5 text-[11px] font-medium text-emerald-700">
                              Recommended
                            </span>
                          ) : null}
                        </span>
                        <span className="block truncate text-xs text-slate-500">{vendorEmail(vendor)}</span>
                      </span>
                    </span>
                    <span className="flex shrink-0 items-center gap-2">
                      {recommendation ? (
                        <button
                          type="button"
                          onClick={(event) => {
                            event.preventDefault();
                            setExpandedRecommendationId(expanded ? null : vendor.id);
                          }}
                          aria-expanded={expanded}
                          aria-label={`Why ${vendorLabel(vendor)} is recommended`}
                          className="inline-flex h-7 w-7 items-center justify-center rounded border border-slate-200 bg-white text-slate-600 hover:bg-slate-100"
                        >
                          <Info size={14} />
                        </button>
                      ) : null}
                      {checked ? <CheckCircle2 size={16} className="text-emerald-600" /> : null}
                    </span>
                  </label>
                  {recommendation && expanded ? (
                    <div className="border-t border-slate-100 px-4 py-3 text-sm">
                      <p className="font-medium text-slate-800">{recommendation.recommendedReasonSummary}</p>
                      <div className="mt-2 grid gap-3 md:grid-cols-2">
                        <div>
                          <p className="text-xs font-medium uppercase text-slate-500">Reasons</p>
                          <ul className="mt-1 list-disc space-y-1 pl-4 text-slate-600">
                            {recommendation.deterministicReasons.map((reason, index) => (
                              <li key={`reason-${index}`}>{reason}</li>
                            ))}
                            {recommendation.llmInsights.map((insight, index) => (
                              <li key={`insight-${index}`}>{insight}</li>
                            ))}
                          </ul>
                        </div>
                        <div>
                          <p className="text-xs font-medium uppercase text-slate-500">Warnings</p>
                          {recommendation.warnings.length > 0 ? (
                            <ul className="mt-1 list-disc space-y-1 pl-4 text-slate-600">
                              {recommendation.warnings.map((warning, index) => (
                                <li key={`warning-${index}`}>{warning}</li>
                              ))}
                            </ul>
                          ) : (
                            <p className="mt-1 text-slate-500">No recommendation warnings.</p>
                          )}
                        </div>
                      </div>
                    </div>
                  ) : null}
                </div>
              );
            })}
          </div>
        )}

        {recommendationsQuery.isError ? (
          <p className="text-sm text-red-600">
            {recommendationsQuery.error instanceof Error
              ? recommendationsQuery.error.message
              : 'Vendor recommendations could not be loaded.'}
          </p>
        ) : null}
        {updateSelection.isError ? (
          <p className="text-sm text-red-600">
            {updateSelection.error instanceof Error ? updateSelection.error.message : 'Vendor selection could not be saved.'}
          </p>
        ) : null}
        {draftSelected.size === 0 ? (
          <p className="text-sm text-slate-500">At least one approved vendor is required before the selection can be saved.</p>
        ) : null}
      </div>
    </SectionCard>
  );
}

export function RfqVendorsPageContent({ rfqId }: { rfqId: string }) {
  const rfqQuery = useRfq(rfqId);
  const vendorsQuery = useRfqVendors(rfqId);
  const selectedQuery = useRequisitionVendorSelection(rfqId);
  const inviteSelected = useInviteSelectedVendors(rfqId);
  const rfq = rfqQuery.data;
  const vendors = React.useMemo(() => vendorsQuery.data ?? [], [vendorsQuery.data]);
  const selectedVendors = React.useMemo(() => selectedQuery.data ?? [], [selectedQuery.data]);
  const isLoading = vendorsQuery.isLoading;
  const invitedVendorIds = React.useMemo(
    () => new Set(vendors.map((vendor) => vendor.vendor_id).filter((vendorId): vendorId is string => vendorId !== null)),
    [vendors],
  );
  const pendingInviteVendorIds = React.useMemo(
    () => selectedVendors.map((vendor) => vendor.vendorId).filter((vendorId) => !invitedVendorIds.has(vendorId)),
    [invitedVendorIds, selectedVendors],
  );
  const inviteDisabled = selectedQuery.isError || pendingInviteVendorIds.length === 0;
  const selectedSubtitle = selectedQuery.isLoading ? 'Loading...' : `${selectedVendors.length} ready for invitation`;
  const inviteSelectedVendors = async () => {
    if (inviteDisabled) {
      return;
    }

    try {
      await inviteSelected.mutateAsync({ vendorIds: pendingInviteVendorIds });
    } catch {
      // Error state is rendered below; keep the action available for retry.
    }
  };

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
          <Button
            size="sm"
            variant="outline"
            onClick={inviteSelectedVendors}
            loading={inviteSelected.isPending}
            disabled={inviteDisabled}
          >
            <Mail size={14} className="mr-1.5" />
            Invite vendors
          </Button>
        }
      />
      {inviteDisabled ? (
        <p className="text-sm text-slate-500">
          {selectedVendors.length === 0 ? 'Select approved vendors to enable invitations.' : 'All selected vendors have been invited.'}
        </p>
      ) : null}
      {inviteSelected.isError ? (
        <p className="text-sm text-red-600">
          {inviteSelected.error instanceof Error ? inviteSelected.error.message : 'Selected vendors could not be invited.'}
        </p>
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
            action={
              <Button
                size="sm"
                onClick={inviteSelectedVendors}
                loading={inviteSelected.isPending}
                disabled={inviteDisabled}
              >
                Invite vendors
              </Button>
            }
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
