'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import { Button } from '@/components/ds/Button';
import { DataTable, type ColumnDef } from '@/components/ds/DataTable';
import { FilterBar, PageHeader } from '@/components/ds/FilterBar';
import { Card } from '@/components/ds/Card';
import { useProjects, type ProjectListItem } from '@/hooks/use-projects';

const PROJECT_COLUMNS: ColumnDef<ProjectListItem>[] = [
  {
    key: 'id',
    label: 'ID',
    width: '120px',
    sortable: true,
    render: (row) => <span className="font-mono text-xs font-medium text-slate-600">{row.id}</span>,
  },
  {
    key: 'name',
    label: 'Project',
    sortable: true,
    render: (row) => (
      <div>
        <div className="text-sm font-medium text-slate-800 leading-tight">{row.name}</div>
        <div className="text-[11px] text-slate-400 mt-0.5">
          {row.clientName ? `Client: ${row.clientName}` : row.clientId ? `Client: ${row.clientId}` : '—'}
        </div>
      </div>
    ),
  },
  {
    key: 'status',
    label: 'Status',
    width: '120px',
    sortable: true,
    render: (row) => <span className="text-xs text-slate-600">{row.status ?? '—'}</span>,
  },
  {
    key: 'startDate',
    label: 'Start',
    width: '140px',
    render: (row) => <span className="text-xs text-slate-600 tabular-nums">{row.startDate ?? '—'}</span>,
  },
  {
    key: 'endDate',
    label: 'End',
    width: '140px',
    render: (row) => <span className="text-xs text-slate-600 tabular-nums">{row.endDate ?? '—'}</span>,
  },
];

export default function ProjectsPage() {
  const router = useRouter();
  const [q, setQ] = React.useState('');
  const { data: projects = [], isLoading, isError, error } = useProjects();

  const filtered = React.useMemo(() => {
    const query = q.trim().toLowerCase();
    if (!query) return projects;
    return projects.filter((p) => p.name.toLowerCase().includes(query) || p.id.toLowerCase().includes(query));
  }, [projects, q]);

  if (isError) {
    return (
      <Card padding="md">
        <div className="text-sm font-semibold text-slate-900">Failed to load projects</div>
        <div className="text-xs text-slate-500 mt-1">{String((error as any)?.message ?? '')}</div>
      </Card>
    );
  }

  return (
    <div className="space-y-4">
      <PageHeader
        title="Projects"
        subtitle={`${filtered.length} projects`}
        actions={
          <Button size="sm" variant="secondary" onClick={() => router.push('/rfqs')}>
            Back to RFQs
          </Button>
        }
      />

      <Card padding="md">
        <FilterBar
          searchValue={q}
          onSearchChange={setQ}
          searchPlaceholder="Search projects…"
          filters={[]}
          activeFilters={[]}
        />
      </Card>

      <DataTable
        columns={PROJECT_COLUMNS}
        rows={filtered}
        loading={isLoading}
        selectable={false}
        expandable={false}
        showActions={false}
        onRowAction={() => {}}
        onRowClick={(row) => router.push(`/projects/${encodeURIComponent(String(row.id))}`)}
      />
    </div>
  );
}

