'use client';

import React from 'react';
import { redirect, useRouter } from 'next/navigation';
import axios from 'axios';
import { useFeatureFlags } from '@/hooks/use-feature-flags';
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
  const isDev = process.env.NODE_ENV === 'development';
  const router = useRouter();
  const [q, setQ] = React.useState('');
  const [showCreateForm, setShowCreateForm] = React.useState(false);
  const [createName, setCreateName] = React.useState('');
  const [createClientId, setCreateClientId] = React.useState('');
  const [createStartDate, setCreateStartDate] = React.useState('');
  const [createEndDate, setCreateEndDate] = React.useState('');
  const [createPmId, setCreatePmId] = React.useState('');
  const { data: flags, isLoading: flagsLoading } = useFeatureFlags();
  const projectsEnabled = flags?.projects === true;
  const { data: projects = [], isLoading, isError, error } = useProjects({
    enabled: !flagsLoading && projectsEnabled,
  });
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

  if (flagsLoading) {
    return (
      <div className="space-y-4">
        <PageHeader title="Projects" subtitle="Loading…" actions={null} />
        <Card padding="md">
          <div className="space-y-2" aria-busy="true">
            <div className="h-4 w-40 rounded bg-slate-200 animate-pulse" />
            <div className="h-24 w-full rounded bg-slate-100 animate-pulse" />
          </div>
        </Card>
      </div>
    );
  }

  if (!projectsEnabled) {
    redirect('/dashboard');
  }

  if (isError) {
    const genericDetail = 'Projects are disabled or not found.';
    const is404 = axios.isAxiosError(error) && error.response?.status === 404;
    const detail = is404
      ? (isDev
          ? 'The API returned 404 — Projects are usually disabled. Set FEATURE_PROJECTS_ENABLED=true in apps/atomy-q/API/.env and restart php artisan serve (or your PHP process).'
          : genericDetail)
      : (isDev ? String((error as Error | null)?.message ?? '') : genericDetail);
    return (
      <Card padding="md">
        <div className="text-sm font-semibold text-slate-900">Failed to load projects</div>
        <div className="text-xs text-slate-500 mt-1">{detail}</div>
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
              <p className="text-xs text-red-600">
                {isDev
                  ? String((createProject.error as Error | null)?.message ?? '')
                  : 'An error occurred while creating the project.'}
              </p>
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

