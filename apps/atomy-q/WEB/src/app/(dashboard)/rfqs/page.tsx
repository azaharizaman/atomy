'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import { Button } from '@/components/ds/Button';
import { FilterBar, PageHeader } from '@/components/ds/FilterBar';
import { StatusBadge } from '@/components/ds/Badge';
import { DataTable, type ColumnDef } from '@/components/ds/DataTable';
import { InlineDetailPanel } from '@/components/ds/Card';
import { useRfqs, type RfqListItem } from '@/hooks/use-rfqs';
import { useProjects } from '@/hooks/use-projects';
import { useFeatureFlags } from '@/hooks/use-feature-flags';

export function getRfqBulkActionLabels(useMocks: boolean): string[] {
  return useMocks
    ? ['Close Selected', 'Archive Selected', 'Assign Owner', 'Export Selected']
    : ['Close Selected', 'Cancel Selected'];
}

function OwnerCell({ name }: { name: string }) {
  const initials = name
    .split(' ')
    .filter(Boolean)
    .map((p) => p[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();

  return (
    <div className="flex items-center gap-1.5 mt-0.5">
      <div className="w-4 h-4 rounded-full bg-slate-100 flex items-center justify-center text-[9px] font-bold text-slate-600">
        {initials}
      </div>
      <span className="text-[11px] text-slate-400">{name}</span>
    </div>
  );
}

export default function RfqsPage() {
  const router = useRouter();
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';
  const [q, setQ] = React.useState('');
  const [status, setStatus] = React.useState('');
  const [owner, setOwner] = React.useState('');
  const [category, setCategory] = React.useState('');
  const [projectId, setProjectId] = React.useState('');

  const [selectedIds, setSelectedIds] = React.useState<(string | number)[]>([]);
  const [expandedId, setExpandedId] = React.useState<string | number | null>(null);

  const [page, setPage] = React.useState(1);

  const { data: flags, isLoading: flagsLoading } = useFeatureFlags();
  const projectsEnabled = flags?.projects === true;

  const { data, isLoading } = useRfqs({ q, status, owner, category, page, projectId });
  const { data: projects = [] } = useProjects({ enabled: !flagsLoading && projectsEnabled });

  React.useEffect(() => {
    setPage(1);
  }, [q, status, owner, category, projectId]);

  React.useEffect(() => {
    if (!flagsLoading && !projectsEnabled && projectId) {
      setProjectId('');
    }
  }, [flagsLoading, projectsEnabled, projectId]);

  const visibleRows = data?.items ?? [];
  const totalPages = data?.meta?.total_pages ?? 1;
  const totalItems = data?.meta?.total ?? visibleRows.length;
  const bulkActions = getRfqBulkActionLabels(useMocks).map((label) => ({
    label,
    onClick: (_ids: Array<string | number>) => { /* TODO: wire live bulk mutations */ },
  }));

  const activeFilters = [
    status ? { key: 'status', label: 'Status', value: status } : null,
    owner ? { key: 'owner', label: 'Owner', value: owner } : null,
    category ? { key: 'category', label: 'Category', value: category } : null,
    projectId ? { key: 'projectId', label: 'Project', value: projectId } : null,
  ].filter(Boolean) as Array<{ key: string; label: string; value: string }>;

  const columns: ColumnDef<RfqListItem>[] = [
    {
      key: 'id',
      label: 'ID',
      width: '90px',
      sortable: true,
      render: (row) => <span className="font-mono text-xs font-medium text-slate-600">{row.id}</span>,
    },
    {
      key: 'title',
      label: 'RFQ',
      sortable: true,
      render: (row) => (
        <div>
          <div className="text-sm font-medium text-slate-800 leading-tight">{row.title}</div>
          <OwnerCell name={row.owner?.name || '—'} />
        </div>
      ),
    },
    {
      key: 'status',
      label: 'Status',
      width: '110px',
      sortable: true,
      render: (row) => <StatusBadge status={row.status} />,
    },
    {
      key: 'deadline',
      label: 'Deadline',
      width: '110px',
      sortable: true,
      render: (row) => <span className="text-xs text-slate-600 tabular-nums">{row.deadline ?? '—'}</span>,
    },
    {
      key: 'estValue',
      label: 'Est. Value',
      width: '120px',
      align: 'right',
      sortable: true,
      render: (row) => <span className="text-sm font-medium text-slate-800 tabular-nums">{row.estValue ?? '—'}</span>,
    },
  ];

  return (
    <div className="space-y-4">
      <PageHeader
        title="Requisitions"
        subtitle={`${totalItems} total requisitions`}
        actions={
          <Button size="sm" variant="primary" onClick={() => router.push('/rfqs/new')}>
            Create RFQ
          </Button>
        }
      />

      <FilterBar
        searchValue={q}
        onSearchChange={setQ}
        searchPlaceholder="Search requisitions…"
        filters={[
          {
            key: 'status',
            label: 'Status',
            value: status,
            onChange: setStatus,
            options: [
              { value: 'active', label: 'Active' },
              { value: 'closed', label: 'Closed' },
              { value: 'awarded', label: 'Awarded' },
              { value: 'archived', label: 'Archived' },
              { value: 'draft', label: 'Draft' },
              { value: 'pending', label: 'Pending' },
            ],
          },
          {
            key: 'owner',
            label: 'Owner',
            value: owner,
            onChange: setOwner,
            options: [
              { value: 'alex', label: 'Alex Kumar' },
              { value: 'sarah', label: 'Sarah Chen' },
              { value: 'marcus', label: 'Marcus Webb' },
              { value: 'priya', label: 'Priya Nair' },
            ],
          },
          {
            key: 'category',
            label: 'Category',
            value: category,
            onChange: setCategory,
            options: [
              { value: 'it', label: 'IT Hardware' },
              { value: 'facilities', label: 'Facilities' },
              { value: 'software', label: 'Software' },
              { value: 'security', label: 'Security' },
            ],
          },
          ...(projectsEnabled
            ? [
                {
                  key: 'projectId',
                  label: 'Project',
                  value: projectId,
                  onChange: setProjectId,
                  options: projects.map((p) => ({ value: p.id, label: p.name })),
                },
              ]
            : []),
        ]}
        activeFilters={activeFilters}
        onRemoveFilter={(key) => {
          if (key === 'status') setStatus('');
          if (key === 'owner') setOwner('');
          if (key === 'category') setCategory('');
          if (key === 'projectId') setProjectId('');
        }}
        onClearAll={() => {
          setStatus('');
          setOwner('');
          setCategory('');
          setProjectId('');
        }}
      />

      <DataTable
        columns={columns}
        rows={visibleRows}
        loading={isLoading}
        selectable
        selectedIds={selectedIds}
        onSelectChange={setSelectedIds}
        expandable
        expandedId={expandedId}
        onExpandChange={setExpandedId}
        expandedIndentColumns={1}
        renderExpanded={(row) => (
          <InlineDetailPanel
            items={[
              { label: 'Category', value: row.category ?? '—' },
              { label: 'Deadline', value: row.deadline ?? '—' },
              { label: 'Vendors', value: row.vendorsCount ?? '—' },
              { label: 'Quotes', value: row.quotesCount ?? '—' },
              { label: 'Est. Value', value: row.estValue ?? '—' },
              { label: 'Savings %', value: row.savings ?? '—' },
            ]}
          />
        )}
        bulkActions={bulkActions}
        showActions={false}
        onRowAction={() => {}}
        onRowClick={(row) => router.push(`/rfqs/${encodeURIComponent(String(row.id))}/overview`)}
      />

      <div className="flex items-center justify-between text-xs text-slate-500">
        <span>Page {page} of {totalPages} ({totalItems} total)</span>
        <div className="flex gap-2">
          <Button size="sm" variant="outline" onClick={() => setPage(page - 1)} disabled={page === 1}>
            Previous
          </Button>
          <Button size="sm" variant="outline" onClick={() => setPage(page + 1)} disabled={page >= totalPages}>
            Next
          </Button>
        </div>
      </div>
    </div>
  );
}
