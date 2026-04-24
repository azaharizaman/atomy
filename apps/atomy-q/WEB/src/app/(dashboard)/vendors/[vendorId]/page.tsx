'use client';

import React from 'react';
import Link from 'next/link';
import { AiNarrativePanel } from '@/components/ai/ai-narrative-panel';
import { Button } from '@/components/ds/Button';
import { PageHeader } from '@/components/ds/FilterBar';
import { SectionCard } from '@/components/ds/Card';
import { StatusBadge } from '@/components/ds/Badge';
import { TextInput } from '@/components/ds/Input';
import { useVendor } from '@/hooks/use-vendor';
import { formatVendorGovernanceWarning, useVendorGovernance } from '@/hooks/use-vendor-governance';
import { useUpdateVendor } from '@/hooks/use-update-vendor';
import { useUpdateVendorStatus } from '@/hooks/use-update-vendor-status';
import type { VendorStatusValue } from '@/hooks/use-vendors';

type StatusAction = {
  label: string;
  status: VendorStatusValue;
};

type VendorEditForm = {
  legalName: string;
  displayName: string;
  registrationNumber: string;
  countryOfRegistration: string;
  primaryContactName: string;
  primaryContactEmail: string;
  primaryContactPhone: string;
};

const STATUS_ACTIONS_BY_STATUS: Record<VendorStatusValue, StatusAction[]> = {
  draft: [{ label: 'Move To Under Review', status: 'under_review' }],
  under_review: [{ label: 'Approve', status: 'approved' }],
  approved: [
    { label: 'Restrict', status: 'restricted' },
    { label: 'Suspend', status: 'suspended' },
    { label: 'Archive', status: 'archived' },
  ],
  restricted: [{ label: 'Approve', status: 'approved' }],
  suspended: [{ label: 'Approve', status: 'approved' }],
  archived: [],
};

function formatVendorStatus(status: VendorStatusValue): string {
  if (status === 'under_review') return 'Under review';
  return status.charAt(0).toUpperCase() + status.slice(1);
}

function getBadgeVariant(status: VendorStatusValue) {
  if (status === 'approved') return { status: 'approved' as const, label: 'Approved' };
  if (status === 'draft') return { status: 'draft' as const, label: 'Draft' };
  if (status === 'archived') return { status: 'archived' as const, label: 'Archived' };
  return { status: 'pending' as const, label: formatVendorStatus(status) };
}

export function VendorDetailPageContent({ vendorId }: { vendorId: string }) {
  const vendorQuery = useVendor(vendorId);
  const governanceQuery = useVendorGovernance(vendorId);
  const updateVendorMutation = useUpdateVendor(vendorId);
  const statusMutation = useUpdateVendorStatus(vendorId);

  const [approvalNote, setApprovalNote] = React.useState('');
  const [statusError, setStatusError] = React.useState('');
  const [isEditing, setIsEditing] = React.useState(false);
  const [editError, setEditError] = React.useState('');
  const [editForm, setEditForm] = React.useState<VendorEditForm | null>(null);

  if (vendorQuery.isLoading) {
    return <p className="text-sm text-slate-500">Loading vendor...</p>;
  }

  if (vendorQuery.isError || !vendorQuery.data) {
    return (
      <div className="space-y-3">
        <PageHeader title="Vendor" subtitle="Unavailable" />
        <SectionCard title="Vendor unavailable">
          <p className="text-sm text-red-700">
            {vendorQuery.error instanceof Error ? vendorQuery.error.message : 'Vendor data could not be loaded.'}
          </p>
        </SectionCard>
      </div>
    );
  }

  const vendor = vendorQuery.data;
  const badge = getBadgeVariant(vendor.status);
  const availableStatusActions = STATUS_ACTIONS_BY_STATUS[vendor.status] ?? [];
  const currentEditForm = editForm ?? {
    legalName: vendor.legalName,
    displayName: vendor.displayName,
    registrationNumber: vendor.registrationNumber,
    countryOfRegistration: vendor.countryOfRegistration,
    primaryContactName: vendor.primaryContactName,
    primaryContactEmail: vendor.primaryContactEmail,
    primaryContactPhone: vendor.primaryContactPhone ?? '',
  };

  const updateEditField = (field: keyof VendorEditForm, value: string) => {
    setEditForm((current) => ({
      ...(current ?? currentEditForm),
      [field]: value,
    }));
  };

  const closeEditForm = () => {
    setEditError('');
    setIsEditing(false);
    setEditForm(null);
  };

  const submitVendorUpdate = () => {
    setEditError('');
    updateVendorMutation.mutate(
      {
        legalName: currentEditForm.legalName.trim(),
        displayName: currentEditForm.displayName.trim(),
        registrationNumber: currentEditForm.registrationNumber.trim(),
        countryOfRegistration: currentEditForm.countryOfRegistration.trim(),
        primaryContactName: currentEditForm.primaryContactName.trim(),
        primaryContactEmail: currentEditForm.primaryContactEmail.trim(),
        primaryContactPhone: currentEditForm.primaryContactPhone.trim() === ''
          ? null
          : currentEditForm.primaryContactPhone.trim(),
      },
      {
        onSuccess: closeEditForm,
        onError: (error) => {
          setEditError(error instanceof Error ? error.message : 'Vendor update failed.');
        },
      },
    );
  };

  const submitStatusAction = (action: StatusAction) => {
    setStatusError('');
    const normalizedApprovalNote = approvalNote.trim();

    if (action.status === 'approved' && normalizedApprovalNote === '') {
      setStatusError('Approval note is required before approving a vendor.');
      return;
    }

    statusMutation.mutate({
      status: action.status,
      approvalNote: action.status === 'approved' ? normalizedApprovalNote : undefined,
    });
  };

  return (
    <div className="space-y-4">
      <PageHeader
        title={vendor.displayName}
        subtitle={vendor.legalName}
        actions={<StatusBadge status={badge.status} label={badge.label} />}
      />

      <SectionCard
        title="Overview"
        actions={
          <Button size="sm" variant="secondary" onClick={() => setIsEditing((value) => !value)}>
            {isEditing ? 'Hide edit form' : 'Edit vendor'}
          </Button>
        }
      >
        <div className="grid gap-3 md:grid-cols-2 text-sm">
          <p><span className="text-slate-500">Registration:</span> {vendor.registrationNumber}</p>
          <p><span className="text-slate-500">Country:</span> {vendor.countryOfRegistration}</p>
          <p><span className="text-slate-500">Primary contact:</span> {vendor.primaryContactName}</p>
          <p><span className="text-slate-500">Primary email:</span> {vendor.primaryContactEmail}</p>
          <p><span className="text-slate-500">Primary phone:</span> {vendor.primaryContactPhone ?? '—'}</p>
          <p><span className="text-slate-500">Current status:</span> {formatVendorStatus(vendor.status)}</p>
        </div>
      </SectionCard>

      <SectionCard
        title="Governance monitoring"
        subtitle="Advisory ESG, compliance, and risk signals"
        actions={
          <Link
            href={`/vendors/${encodeURIComponent(vendorId)}/esg-compliance`}
            className="inline-flex h-7 items-center justify-center rounded border border-slate-300 bg-white px-3 text-xs font-medium text-slate-700 hover:bg-slate-50"
          >
            Review
          </Link>
        }
      >
        {governanceQuery.data?.narrative ? (
          <div className="mb-4">
            <AiNarrativePanel
              featureKey="governance_ai_narrative"
              title="AI Governance Narrative"
              subtitle="Assistive interpretation of the deterministic governance record."
              summary={governanceQuery.data.narrative}
              fallbackCopy="Governance narrative is unavailable. Continue with the factual governance record."
            />
          </div>
        ) : null}
        {governanceQuery.isLoading ? (
          <p className="text-sm text-slate-500">Loading governance summary...</p>
        ) : governanceQuery.isError ? (
          <p className="text-sm text-red-600">
            {governanceQuery.error instanceof Error
              ? governanceQuery.error.message
              : 'Vendor governance summary could not be loaded.'}
          </p>
        ) : governanceQuery.data && governanceQuery.data.warningFlags.length > 0 ? (
          <div className="flex flex-wrap gap-2">
            {governanceQuery.data.warningFlags.map((flag) => (
              <span key={flag} className="rounded border border-amber-200 bg-amber-50 px-2 py-1 text-xs font-medium text-amber-800">
                {formatVendorGovernanceWarning(flag)}
              </span>
            ))}
          </div>
        ) : (
          <p className="text-sm text-slate-500">No governance warnings recorded.</p>
        )}
      </SectionCard>

      {isEditing ? (
        <SectionCard title="Edit vendor" subtitle="Update vendor master core fields">
          <div className="space-y-4">
            <div className="grid gap-3 md:grid-cols-2">
              <TextInput
                label="Legal name"
                value={currentEditForm.legalName}
                onChange={(event) => updateEditField('legalName', event.target.value)}
              />
              <TextInput
                label="Display name"
                value={currentEditForm.displayName}
                onChange={(event) => updateEditField('displayName', event.target.value)}
              />
              <TextInput
                label="Registration number"
                value={currentEditForm.registrationNumber}
                onChange={(event) => updateEditField('registrationNumber', event.target.value)}
              />
              <TextInput
                label="Country of registration"
                value={currentEditForm.countryOfRegistration}
                onChange={(event) => updateEditField('countryOfRegistration', event.target.value)}
              />
              <TextInput
                label="Primary contact name"
                value={currentEditForm.primaryContactName}
                onChange={(event) => updateEditField('primaryContactName', event.target.value)}
              />
              <TextInput
                label="Primary contact email"
                value={currentEditForm.primaryContactEmail}
                onChange={(event) => updateEditField('primaryContactEmail', event.target.value)}
              />
              <TextInput
                label="Primary contact phone"
                value={currentEditForm.primaryContactPhone}
                onChange={(event) => updateEditField('primaryContactPhone', event.target.value)}
              />
            </div>
            <div className="flex flex-wrap gap-2">
              <Button
                size="sm"
                variant="primary"
                loading={updateVendorMutation.isPending}
                onClick={submitVendorUpdate}
              >
                Save vendor
              </Button>
              <Button size="sm" variant="secondary" onClick={closeEditForm}>
                Cancel
              </Button>
            </div>
            {editError !== '' ? <p className="text-xs text-red-600">{editError}</p> : null}
            {updateVendorMutation.isError ? (
              <p className="text-xs text-red-600">
                {updateVendorMutation.error instanceof Error
                  ? updateVendorMutation.error.message
                  : 'Vendor update failed.'}
              </p>
            ) : null}
          </div>
        </SectionCard>
      ) : null}

      {vendor.approvalRecord ? (
        <SectionCard title="Approval metadata">
          <div className="space-y-1 text-sm text-slate-700">
            <p>Approved by {vendor.approvalRecord.approvedByUserId}</p>
            <p>Approved at {vendor.approvalRecord.approvedAt}</p>
            <p>Note: {vendor.approvalRecord.approvalNote}</p>
          </div>
        </SectionCard>
      ) : null}

      <SectionCard title="Status controls" subtitle="Manual operational status transitions">
        <div className="space-y-3">
          <label className="block text-xs text-slate-600">
            Approval note
            <textarea
              value={approvalNote}
              onChange={(event) => {
                setApprovalNote(event.target.value);
                if (statusError !== '') {
                  setStatusError('');
                }
              }}
              className="mt-1 w-full rounded border border-slate-300 px-2 py-1.5 text-sm"
              rows={3}
            />
          </label>
          <div className="flex flex-wrap gap-2">
            {availableStatusActions.map((action) => (
              <Button
                key={action.status}
                size="sm"
                variant="secondary"
                disabled={statusMutation.isPending}
                onClick={() => submitStatusAction(action)}
              >
                {action.label}
              </Button>
            ))}
            {availableStatusActions.length === 0 ? (
              <p className="text-xs text-slate-500">No further transitions are available from this status.</p>
            ) : null}
          </div>
          {statusMutation.isError ? (
            <p className="text-xs text-red-600">
              {statusMutation.error instanceof Error ? statusMutation.error.message : 'Status update failed.'}
            </p>
          ) : null}
          {statusError !== '' ? <p className="text-xs text-red-600">{statusError}</p> : null}
        </div>
      </SectionCard>
    </div>
  );
}

export default function VendorDetailPage({ params }: { params: Promise<{ vendorId: string }> }) {
  const { vendorId } = React.use(params);
  return <VendorDetailPageContent vendorId={vendorId} />;
}
