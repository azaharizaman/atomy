'use client';

import React from 'react';
import Link from 'next/link';
import { AlertTriangle, Loader2, Plus } from 'lucide-react';

import { Button } from '@/components/ds/Button';
import { Card, EmptyState, InfoGrid, SectionCard } from '@/components/ds/Card';
import { PageHeader } from '@/components/ds/FilterBar';
import { SelectInput, TextInput } from '@/components/ds/Input';
import { StatusBadge } from '@/components/ds/Badge';
import { useCreateVendor } from '@/hooks/use-create-vendor';
import { useVendors, type VendorRow, type VendorStatusValue } from '@/hooks/use-vendors';

type CreateVendorFormState = {
  legalName: string;
  displayName: string;
  registrationNumber: string;
  countryOfRegistration: string;
  primaryContactName: string;
  primaryContactEmail: string;
  primaryContactPhone: string;
};

const EMPTY_FORM: CreateVendorFormState = {
  legalName: '',
  displayName: '',
  registrationNumber: '',
  countryOfRegistration: '',
  primaryContactName: '',
  primaryContactEmail: '',
  primaryContactPhone: '',
};

const STATUS_OPTIONS: Array<{ value: string; label: string }> = [
  { value: 'draft', label: 'Draft' },
  { value: 'under_review', label: 'Under review' },
  { value: 'approved', label: 'Approved' },
  { value: 'restricted', label: 'Restricted' },
  { value: 'suspended', label: 'Suspended' },
  { value: 'archived', label: 'Archived' },
];

const ISO_COUNTRY_CODES = new Set([
  'AD', 'AE', 'AF', 'AG', 'AI', 'AL', 'AM', 'AO', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AW', 'AX', 'AZ',
  'BA', 'BB', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BL', 'BM', 'BN', 'BO', 'BQ', 'BR', 'BS',
  'BT', 'BV', 'BW', 'BY', 'BZ', 'CA', 'CC', 'CD', 'CF', 'CG', 'CH', 'CI', 'CK', 'CL', 'CM', 'CN',
  'CO', 'CR', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ', 'DE', 'DJ', 'DK', 'DM', 'DO', 'DZ', 'EC', 'EE',
  'EG', 'EH', 'ER', 'ES', 'ET', 'FI', 'FJ', 'FK', 'FM', 'FO', 'FR', 'GA', 'GB', 'GD', 'GE', 'GF',
  'GG', 'GH', 'GI', 'GL', 'GM', 'GN', 'GP', 'GQ', 'GR', 'GS', 'GT', 'GU', 'GW', 'GY', 'HK', 'HM',
  'HN', 'HR', 'HT', 'HU', 'ID', 'IE', 'IL', 'IM', 'IN', 'IO', 'IQ', 'IR', 'IS', 'IT', 'JE', 'JM',
  'JO', 'JP', 'KE', 'KG', 'KH', 'KI', 'KM', 'KN', 'KP', 'KR', 'KW', 'KY', 'KZ', 'LA', 'LB', 'LC',
  'LI', 'LK', 'LR', 'LS', 'LT', 'LU', 'LV', 'LY', 'MA', 'MC', 'MD', 'ME', 'MF', 'MG', 'MH', 'MK',
  'ML', 'MM', 'MN', 'MO', 'MP', 'MQ', 'MR', 'MS', 'MT', 'MU', 'MV', 'MW', 'MX', 'MY', 'MZ', 'NA',
  'NC', 'NE', 'NF', 'NG', 'NI', 'NL', 'NO', 'NP', 'NR', 'NU', 'NZ', 'OM', 'PA', 'PE', 'PF', 'PG',
  'PH', 'PK', 'PL', 'PM', 'PN', 'PR', 'PS', 'PT', 'PW', 'PY', 'QA', 'RE', 'RO', 'RS', 'RU', 'RW',
  'SA', 'SB', 'SC', 'SD', 'SE', 'SG', 'SH', 'SI', 'SJ', 'SK', 'SL', 'SM', 'SN', 'SO', 'SR', 'SS',
  'ST', 'SV', 'SX', 'SY', 'SZ', 'TC', 'TD', 'TF', 'TG', 'TH', 'TJ', 'TK', 'TL', 'TM', 'TN', 'TO',
  'TR', 'TT', 'TV', 'TW', 'TZ', 'UA', 'UG', 'UM', 'US', 'UY', 'UZ', 'VA', 'VC', 'VE', 'VG', 'VI',
  'VN', 'VU', 'WF', 'WS', 'YE', 'YT', 'ZA', 'ZM', 'ZW',
]);

function normalizeCountryCode(value: string): string | null {
  const normalized = value.trim().toUpperCase();

  return normalized.length === 2 && ISO_COUNTRY_CODES.has(normalized) ? normalized : null;
}

function getVendorBadge(status: VendorStatusValue) {
  switch (status) {
    case 'approved':
      return { status: 'approved' as const, label: 'Approved' };
    case 'under_review':
      return { status: 'pending' as const, label: 'Under review' };
    case 'restricted':
      return { status: 'rejected' as const, label: 'Restricted' };
    case 'suspended':
      return { status: 'locked' as const, label: 'Suspended' };
    case 'archived':
      return { status: 'archived' as const, label: 'Archived' };
    case 'draft':
    default:
      return { status: 'draft' as const, label: 'Draft' };
  }
}

function VendorRowCard({ vendor }: { vendor: VendorRow }) {
  const badge = getVendorBadge(vendor.status);

  return (
    <Link
      href={`/vendors/${encodeURIComponent(vendor.id)}`}
      className="block rounded-lg border border-slate-200 bg-white p-4 transition-colors hover:border-indigo-200 hover:bg-indigo-50/40"
    >
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div className="min-w-0">
          <p className="text-sm font-semibold text-slate-900">{vendor.displayName}</p>
          <p className="text-xs text-slate-500">{vendor.legalName}</p>
        </div>
        <StatusBadge status={badge.status} label={badge.label} />
      </div>

      <div className="mt-3">
        <InfoGrid
          cols={4}
          items={[
            { label: 'Registration', value: vendor.registrationNumber },
            { label: 'Country', value: vendor.countryOfRegistration },
            { label: 'Contact', value: vendor.primaryContactName },
            { label: 'Email', value: vendor.primaryContactEmail },
          ]}
        />
      </div>
    </Link>
  );
}

function VendorsPageContent() {
  const [search, setSearch] = React.useState('');
  const [statusFilter, setStatusFilter] = React.useState('');
  const [showCreateForm, setShowCreateForm] = React.useState(false);
  const [createError, setCreateError] = React.useState('');
  const [form, setForm] = React.useState<CreateVendorFormState>(EMPTY_FORM);

  const vendorsQuery = useVendors({
    q: search.trim() === '' ? undefined : search,
    status: statusFilter === '' ? undefined : (statusFilter as VendorStatusValue),
  });
  const createVendor = useCreateVendor();

  const vendors = vendorsQuery.data?.items ?? [];
  const total = vendorsQuery.data?.meta.total ?? vendors.length;
  const isLoading = Boolean(vendorsQuery.isLoading);
  const errorMessage =
    vendorsQuery.error instanceof Error ? vendorsQuery.error.message : 'The vendor roster could not be loaded.';

  const submitCreateVendor = () => {
    setCreateError('');
    const countryOfRegistration = normalizeCountryCode(form.countryOfRegistration);
    if (countryOfRegistration === null) {
      setCreateError('Country of registration must be a valid two-letter ISO 3166-1 alpha-2 code.');
      return;
    }

    createVendor.mutate(
      {
        legalName: form.legalName.trim(),
        displayName: form.displayName.trim(),
        registrationNumber: form.registrationNumber.trim(),
        countryOfRegistration,
        primaryContactName: form.primaryContactName.trim(),
        primaryContactEmail: form.primaryContactEmail.trim(),
        primaryContactPhone: form.primaryContactPhone.trim() === '' ? null : form.primaryContactPhone.trim(),
      },
      {
        onSuccess: () => {
          setCreateError('');
          setShowCreateForm(false);
          setForm(EMPTY_FORM);
        },
        onError: (error) => {
          setCreateError(error instanceof Error ? error.message : 'Create vendor failed.');
        },
      },
    );
  };

  return (
    <div className="space-y-4">
      <PageHeader
        title="Vendors"
        subtitle={`${total} vendors`}
        actions={
          <Button size="sm" variant="primary" onClick={() => setShowCreateForm((value) => !value)}>
            <Plus size={14} className="mr-1.5" />
            {showCreateForm ? 'Hide create form' : 'Create vendor'}
          </Button>
        }
      />

      <Card padding="md">
        <div className="grid gap-3 md:grid-cols-[minmax(0,1fr),220px] md:items-end">
          <TextInput
            label="Search vendors"
            value={search}
            onChange={(event) => setSearch(event.target.value)}
            placeholder="Search by vendor name, email, or registration number"
          />
          <SelectInput
            label="Status filter"
            value={statusFilter}
            onChange={(event) => setStatusFilter(event.target.value)}
            placeholder="All statuses"
            options={STATUS_OPTIONS}
          />
        </div>
      </Card>

      {showCreateForm ? (
        <SectionCard title="Create vendor" subtitle="Register a new vendor record">
          <div className="space-y-4">
            <div className="grid gap-3 md:grid-cols-2">
              <TextInput
                label="Legal name"
                value={form.legalName}
                onChange={(event) => setForm((current) => ({ ...current, legalName: event.target.value }))}
              />
              <TextInput
                label="Display name"
                value={form.displayName}
                onChange={(event) => setForm((current) => ({ ...current, displayName: event.target.value }))}
              />
              <TextInput
                label="Registration number"
                value={form.registrationNumber}
                onChange={(event) => setForm((current) => ({ ...current, registrationNumber: event.target.value }))}
              />
              <TextInput
                label="Country of registration"
                value={form.countryOfRegistration}
                onChange={(event) => setForm((current) => ({ ...current, countryOfRegistration: event.target.value }))}
              />
              <TextInput
                label="Primary contact name"
                value={form.primaryContactName}
                onChange={(event) => setForm((current) => ({ ...current, primaryContactName: event.target.value }))}
              />
              <TextInput
                label="Primary contact email"
                value={form.primaryContactEmail}
                onChange={(event) => setForm((current) => ({ ...current, primaryContactEmail: event.target.value }))}
              />
              <TextInput
                label="Primary contact phone"
                value={form.primaryContactPhone}
                onChange={(event) => setForm((current) => ({ ...current, primaryContactPhone: event.target.value }))}
              />
            </div>

            <div className="flex items-center gap-2">
              <Button
                size="sm"
                variant="primary"
                loading={createVendor.isPending}
                onClick={submitCreateVendor}
              >
                Submit vendor
              </Button>
              <Button
                size="sm"
                variant="secondary"
                onClick={() => {
                  setCreateError('');
                  setForm(EMPTY_FORM);
                  setShowCreateForm(false);
                }}
              >
                Cancel
              </Button>
            </div>

            {createError !== '' ? <p className="text-xs text-red-600">{createError}</p> : null}
          </div>
        </SectionCard>
      ) : null}

      <SectionCard
        title="Vendor roster"
        subtitle={isLoading ? 'Loading vendors…' : `${vendors.length} matching`}
      >
        {isLoading ? (
          <div className="flex items-center justify-center gap-2 py-10 text-sm text-slate-500">
            <Loader2 size={16} className="animate-spin" />
            Loading vendors…
          </div>
        ) : vendorsQuery.isError ? (
          <EmptyState
            icon={<AlertTriangle size={20} />}
            title="Could not load vendors"
            description={errorMessage}
          />
        ) : vendors.length === 0 ? (
          <EmptyState
            title="No vendors found"
            description="Create the first vendor or adjust the filters to narrow the roster."
            action={
              <Button size="sm" variant="primary" onClick={() => setShowCreateForm(true)}>
                Create vendor
              </Button>
            }
          />
        ) : (
          <div className="space-y-3">
            {vendors.map((vendor) => (
              <VendorRowCard key={vendor.id} vendor={vendor} />
            ))}
          </div>
        )}
      </SectionCard>
    </div>
  );
}

export default function VendorsPage() {
  return <VendorsPageContent />;
}
