'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import { Button } from '@/components/ds/Button';
import { DataTable, type ColumnDef } from '@/components/ds/DataTable';
import { FilterBar, PageHeader } from '@/components/ds/FilterBar';
import { Card } from '@/components/ds/Card';
import { useProjects, type ProjectListItem } from '@/hooks/use-projects';
import { useCreateProject } from '@/hooks/use-create-project';

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
  const [showCreateForm, setShowCreateForm] = React.useState(false);
  const [createName, setCreateName] = React.useState('');
  const [createClientId, setCreateClientId] = React.useState('');
  const [createStartDate, setCreateStartDate] = React.useState('');
  const [createEndDate, setCreateEndDate] = React.useState('');
  const [createPmId, setCreatePmId] = React.useState('');
  const { data: projects = [], isLoading, isError, error } = useProjects();
  const createProject = useCreateProject();

  const handleCreateSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!createName.trim() || !createClientId.trim() || !createStartDate || !createEndDate || !createPmId.trim()) return;
    createProject.mutate(
      {
        name: createName.trim(),
        client_id: createClientId.trim(),
        start_date: createStartDate,
        end_date: createEndDate,
        project_manager_id: createPmId.trim(),
      },
      {
        onSuccess: (result) => {
          setShowCreateForm(false);
          setCreateName('');
          setCreateClientId('');
          setCreateStartDate('');
          setCreateEndDate('');
          setCreatePmId('');
          router.push(`/projects/${encodeURIComponent(result.id)}`);
        },
      }
    );
  };

  const filtered = React.useMemo(() => {
    const query = q.trim().toLowerCase();
    if (!query) return projects;
    return projects.filter((p) => p.name.toLowerCase().includes(query) || p.id.toLowerCase().includes(query));
  }, [projects, q]);

  if (isError) {
    return (
      <Card padding="md">
        <div className="text-sm font-semibold text-slate-900">Failed to load projects</div>
        <div className="text-xs text-slate-500 mt-1">{String((error as Error | null)?.message ?? '')}</div>
      </Card>
    );
  }

  return (
    <div className="space-y-4">
      <PageHeader
        title="Projects"
        subtitle={`${filtered.length} projects`}
        actions={
          <div className="flex gap-2">
            <Button size="sm" variant="primary" onClick={() => setShowCreateForm(true)}>
              Create project
            </Button>
            <Button size="sm" variant="secondary" onClick={() => router.push('/rfqs')}>
              Back to RFQs
            </Button>
          </div>
        }
      />

      {showCreateForm && (
        <Card padding="md">
          <h2 className="text-sm font-semibold text-slate-900 mb-3">New project</h2>
          <form onSubmit={handleCreateSubmit} className="space-y-3 max-w-md">
            <div>
              <label className="block text-xs font-medium text-slate-600 mb-1">Name</label>
              <input
                type="text"
                value={createName}
                onChange={(e) => setCreateName(e.target.value)}
                className="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"
                required
              />
            </div>
            <div>
              <label className="block text-xs font-medium text-slate-600 mb-1">Client ID</label>
              <input
                type="text"
                value={createClientId}
                onChange={(e) => setCreateClientId(e.target.value)}
                className="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"
                required
              />
            </div>
            <div>
              <label className="block text-xs font-medium text-slate-600 mb-1">Start date</label>
              <input
                type="date"
                value={createStartDate}
                onChange={(e) => setCreateStartDate(e.target.value)}
                className="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"
                required
              />
            </div>
            <div>
              <label className="block text-xs font-medium text-slate-600 mb-1">End date</label>
              <input
                type="date"
                value={createEndDate}
                onChange={(e) => setCreateEndDate(e.target.value)}
                className="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"
                required
              />
            </div>
            <div>
              <label className="block text-xs font-medium text-slate-600 mb-1">Project manager ID</label>
              <input
                type="text"
                value={createPmId}
                onChange={(e) => setCreatePmId(e.target.value)}
                className="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"
                required
              />
            </div>
            <div className="flex gap-2">
              <Button type="submit" size="sm" variant="primary" disabled={createProject.isPending}>
                {createProject.isPending ? 'Creating…' : 'Create'}
              </Button>
              <Button type="button" size="sm" variant="secondary" onClick={() => setShowCreateForm(false)}>
                Cancel
              </Button>
            </div>
            {createProject.isError && (
              <p className="text-xs text-red-600">{String((createProject.error as Error)?.message)}</p>
            )}
          </form>
        </Card>
      )}

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

