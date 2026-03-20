'use client';

import React from 'react';
import { useParams, useRouter } from 'next/navigation';
import axios from 'axios';
import { Button } from '@/components/ds/Button';
import { Card, InlineDetailPanel } from '@/components/ds/Card';
import { PageHeader } from '@/components/ds/FilterBar';
import { useProject } from '@/hooks/use-project';
import { useProjectHealth } from '@/hooks/use-project-health';
import { useProjectRfqs } from '@/hooks/use-project-rfqs';
import { useProjectTasks } from '@/hooks/use-project-tasks';
import { useUpdateProject } from '@/hooks/use-update-project';
import { useUpdateProjectStatus, PROJECT_STATUSES } from '@/hooks/use-update-project-status';
import { useProjectAcl, type ProjectAclEntry, type ProjectAclRole } from '@/hooks/use-project-acl';
import { useUpdateProjectAcl } from '@/hooks/use-update-project-acl';

type AclDraftEntry = ProjectAclEntry & { draftId: string };

/** Stable fallback so `useEffect([acl])` does not see a new [] every render while loading. */
const EMPTY_PROJECT_ACL: ProjectAclEntry[] = [];

function createAclDraftId() {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID();
  }
  return `acl-draft-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
}

export default function ProjectDetailPage() {
  const router = useRouter();
  const params = useParams<{ projectId: string }>();
  const projectId = params.projectId;

  const [editMode, setEditMode] = React.useState(false);
  const [editName, setEditName] = React.useState('');
  const [editClientId, setEditClientId] = React.useState('');
  const [editStartDate, setEditStartDate] = React.useState('');
  const [editEndDate, setEditEndDate] = React.useState('');
  const [editPmId, setEditPmId] = React.useState('');

  const { data: project, isLoading: projectLoading, error: projectError } = useProject(projectId);
  const { data: health, isLoading: healthLoading, isError: healthIsError, error: healthError } = useProjectHealth(projectId);
  const { data: rfqs = [], isLoading: rfqsLoading, isError: rfqsError } = useProjectRfqs(projectId);
  const { data: tasks = [], isLoading: tasksLoading, isError: tasksError } = useProjectTasks(projectId);
  const { data: aclData, isLoading: aclLoading, isError: aclIsError, error: aclError } = useProjectAcl(projectId);
  const acl = aclData ?? EMPTY_PROJECT_ACL;
  const updateProject = useUpdateProject(projectId);
  const updateStatus = useUpdateProjectStatus(projectId);
  const updateAcl = useUpdateProjectAcl(projectId);

  const [aclDraft, setAclDraft] = React.useState<AclDraftEntry[]>([]);
  const [aclDirty, setAclDirty] = React.useState(false);

  React.useEffect(() => {
    if (project && !editMode) {
      setEditName(project.name ?? '');
      setEditClientId(project.clientId ?? '');
      setEditStartDate(project.startDate?.slice(0, 10) ?? '');
      setEditEndDate(project.endDate?.slice(0, 10) ?? '');
      setEditPmId('');
    }
  }, [project, editMode]);

  const aclSyncKey = React.useMemo(
    () => acl.map((e) => `${e.userId}:${e.role}`).join('|'),
    [acl],
  );

  React.useEffect(
    () => {
      if (aclDirty) {
        return;
      }
      setAclDraft(
        acl.map((entry) => ({
          ...entry,
          draftId: createAclDraftId(),
        })),
      );
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps -- `acl` omitted: stable EMPTY_PROJECT_ACL + aclSyncKey tracks content
    [aclSyncKey, aclDirty],
  );

  const normalizedAcl = React.useMemo(
    () =>
      aclDraft
        .map((e) => ({ userId: e.userId.trim(), role: e.role }))
        .filter((e) => e.userId !== ''),
    [aclDraft]
  );

  const isAxios404 = axios.isAxiosError(projectError) && projectError.response?.status === 404;
  const projectErrorData = axios.isAxiosError(projectError) ? projectError.response?.data : undefined;
  const projectErrorObj =
    projectErrorData != null && typeof projectErrorData === 'object' && !Array.isArray(projectErrorData)
      ? (projectErrorData as Record<string, unknown>)
      : null;
  const featureDisabledSignal = axios.isAxiosError(projectError) && projectErrorObj?.code === 'projects_not_enabled';

  if (projectError && !featureDisabledSignal && !isAxios404) {
    return (
      <Card padding="md">
        <h1 className="text-lg font-semibold text-slate-900">Unable to load project</h1>
        <p className="text-sm text-slate-500 mt-1">Something went wrong while loading this project. Please try again.</p>
        <div className="mt-3">
          <Button size="sm" variant="secondary" onClick={() => router.push('/projects')}>
            Back to projects
          </Button>
        </div>
      </Card>
    );
  }

  if (featureDisabledSignal) {
    return (
      <Card padding="md">
        <h1 className="text-lg font-semibold text-slate-900">Projects not enabled</h1>
        <p className="text-sm text-slate-500 mt-1">Enable the backend feature flags to use Projects.</p>
        <div className="mt-3">
          <Button size="sm" variant="secondary" onClick={() => router.push('/projects')}>
            Back to projects
          </Button>
        </div>
      </Card>
    );
  }

  if (isAxios404) {
    return (
      <Card padding="md">
        <h1 className="text-lg font-semibold text-slate-900">Project not found</h1>
        <p className="text-sm text-slate-500 mt-1">This project may not exist, or Projects may be disabled.</p>
        <div className="mt-3">
          <Button size="sm" variant="secondary" onClick={() => router.push('/projects')}>
            Back to projects
          </Button>
        </div>
      </Card>
    );
  }

  return (
    <div className="space-y-4">
      <PageHeader
        title={projectLoading ? 'Project' : project?.name ?? 'Project'}
        subtitle={project?.status ? `Status: ${project.status}` : undefined}
        actions={
          <div className="flex gap-2">
            {!editMode ? (
              <Button size="sm" variant="secondary" onClick={() => setEditMode(true)}>
                Edit
              </Button>
            ) : null}
            <Button size="sm" variant="secondary" onClick={() => router.push('/projects')}>
              Back
            </Button>
          </div>
        }
      />

      {editMode && project && (
        <Card padding="md">
          <h2 className="text-sm font-semibold text-slate-900 mb-3">Edit project</h2>
          <form
            onSubmit={(e) => {
              e.preventDefault();
              updateProject.mutate(
                {
                  name: editName.trim(),
                  client_id: editClientId.trim(),
                  start_date: editStartDate || undefined,
                  end_date: editEndDate || undefined,
                  project_manager_id: editPmId.trim() || undefined,
                },
                { onSuccess: () => setEditMode(false) }
              );
            }}
            className="space-y-3 max-w-md"
          >
            <div>
              <label className="block text-xs font-medium text-slate-600 mb-1">Name</label>
              <input
                type="text"
                value={editName}
                onChange={(e) => setEditName(e.target.value)}
                className="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"
              />
            </div>
            <div>
              <label className="block text-xs font-medium text-slate-600 mb-1">Client ID</label>
              <input
                type="text"
                value={editClientId}
                onChange={(e) => setEditClientId(e.target.value)}
                className="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"
              />
            </div>
            <div>
              <label className="block text-xs font-medium text-slate-600 mb-1">Start date</label>
              <input
                type="date"
                value={editStartDate}
                onChange={(e) => setEditStartDate(e.target.value)}
                className="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"
              />
            </div>
            <div>
              <label className="block text-xs font-medium text-slate-600 mb-1">End date</label>
              <input
                type="date"
                value={editEndDate}
                onChange={(e) => setEditEndDate(e.target.value)}
                className="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"
              />
            </div>
            <div>
              <label className="block text-xs font-medium text-slate-600 mb-1">Project manager ID</label>
              <input
                type="text"
                value={editPmId}
                onChange={(e) => setEditPmId(e.target.value)}
                placeholder="Optional"
                className="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"
              />
            </div>
            <div className="flex gap-2">
              <Button type="submit" size="sm" variant="primary" disabled={updateProject.isPending}>
                Save
              </Button>
              <Button type="button" size="sm" variant="secondary" onClick={() => setEditMode(false)}>
                Cancel
              </Button>
            </div>
            {updateProject.isError && (
              <p className="text-xs text-red-600">{String((updateProject.error as Error | null)?.message ?? '')}</p>
            )}
          </form>
        </Card>
      )}

      {!editMode && project && (
        <Card padding="md">
          <div className="flex items-center gap-3 flex-wrap">
            <span className="text-xs font-medium text-slate-600">Status</span>
            <select
              value={project.status ?? 'planning'}
              onChange={(e) => {
                const v = e.target.value as (typeof PROJECT_STATUSES)[number];
                if (PROJECT_STATUSES.includes(v)) updateStatus.mutate(v);
              }}
              className="rounded border border-slate-300 px-2 py-1 text-sm"
              disabled={updateStatus.isPending}
            >
              {PROJECT_STATUSES.map((s) => (
                <option key={s} value={s}>
                  {s}
                </option>
              ))}
            </select>
          </div>
        </Card>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-3">
        <Card padding="md">
          <div className="text-xs text-slate-500">Overall score</div>
          <div className="text-2xl font-semibold text-slate-900 tabular-nums mt-1">
            {healthLoading ? 'Loading…' : health?.overallScore != null ? `${Math.round(health.overallScore)}%` : '—'}
          </div>
        </Card>
        <Card padding="md">
          <div className="text-xs text-slate-500">Labor health</div>
          <div className="text-2xl font-semibold text-slate-900 tabular-nums mt-1">
            {healthLoading ? 'Loading…' : health?.labor?.healthPercentage != null ? `${Math.round(health.labor.healthPercentage)}%` : '—'}
          </div>
          <div className="text-xs text-slate-500 mt-1">
            {health?.labor?.actualHours != null ? `${health.labor.actualHours} hours` : ''}
          </div>
        </Card>
        <Card padding="md">
          <div className="text-xs text-slate-500">Timeline</div>
          <div className="text-2xl font-semibold text-slate-900 tabular-nums mt-1">
            {healthLoading ? 'Loading…' : health?.timeline?.completionPercentage != null ? `${Math.round(health.timeline.completionPercentage)}%` : '—'}
          </div>
          <div className="text-xs text-slate-500 mt-1">
            {health?.timeline?.completedMilestones != null && health?.timeline?.totalMilestones != null
              ? `${health.timeline.completedMilestones}/${health.timeline.totalMilestones} milestones`
              : ''}
          </div>
        </Card>
      </div>

      {healthIsError && (
        <Card padding="md">
          <div className="text-sm text-red-700">
            Failed to load health.{' '}
            <span className="text-xs text-red-600">{String((healthError as Error | null)?.message ?? '')}</span>
          </div>
        </Card>
      )}

      <Card padding="md">
        {projectLoading ? (
          <div className="space-y-2" aria-busy="true">
            <div className="h-4 w-32 rounded bg-slate-200 animate-pulse" />
            <div className="h-4 w-24 rounded bg-slate-200 animate-pulse" />
            <div className="h-4 w-28 rounded bg-slate-200 animate-pulse" />
          </div>
        ) : (
          <InlineDetailPanel
            items={[
              { label: 'Client', value: (project?.clientName ?? project?.clientId) ?? '—' },
              { label: 'Start', value: project?.startDate ?? '—' },
              { label: 'End', value: project?.endDate ?? '—' },
              { label: 'Budget type', value: project?.budgetType ?? '—' },
              {
                label: 'Completion %',
                value: project?.completionPercentage != null ? String(project.completionPercentage) : '—',
              },
            ]}
          />
        )}
      </Card>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-3">
        <Card padding="md">
          <div className="flex items-center justify-between">
            <div>
              <div className="text-sm font-semibold text-slate-900">RFQs</div>
              <div className="text-xs text-slate-500 mt-0.5">{rfqs.length} linked</div>
            </div>
          </div>
          <div className="mt-3 space-y-2">
            {rfqsLoading ? (
              <div className="text-sm text-slate-500 animate-pulse">Loading RFQs…</div>
            ) : rfqsError ? (
              <div className="text-sm text-red-600">Failed to load RFQs.</div>
            ) : rfqs.length === 0 ? (
              <div className="text-sm text-slate-500">No RFQs linked yet.</div>
            ) : (
              <>
                {rfqs.slice(0, 8).map((r) => (
                  <button
                    key={r.id}
                    type="button"
                    className="w-full text-left p-2 rounded border border-slate-200 hover:border-slate-300 bg-white"
                    onClick={() => router.push(`/rfqs/${encodeURIComponent(r.id)}/overview`)}
                  >
                    <div className="text-sm font-medium text-slate-900">{r.title ?? 'RFQ'}</div>
                    <div className="text-xs text-slate-500 mt-0.5">{r.status ?? '—'}</div>
                  </button>
                ))}
                {rfqs.length > 8 && (
                  <button
                    type="button"
                    className="text-xs text-slate-600 hover:text-slate-800 underline"
                    onClick={() => router.push('/rfqs')}
                  >
                    View all ({rfqs.length})
                  </button>
                )}
              </>
            )}
          </div>
        </Card>

        <Card padding="md">
          <div className="flex items-center justify-between">
            <div>
              <div className="text-sm font-semibold text-slate-900">Tasks</div>
              <div className="text-xs text-slate-500 mt-0.5">{tasks.length} linked</div>
            </div>
          </div>
          <div className="mt-3 space-y-2">
            {tasksLoading ? (
              <div className="text-sm text-slate-500 animate-pulse">Loading tasks…</div>
            ) : tasksError ? (
              <div className="text-sm text-red-600">Failed to load tasks.</div>
            ) : tasks.length === 0 ? (
              <div className="text-sm text-slate-500">No tasks linked yet.</div>
            ) : (
              <>
                {tasks.slice(0, 8).map((t) => (
                  <div key={t.id} className="p-2 rounded border border-slate-200 bg-white">
                    <div className="text-sm font-medium text-slate-900">{t.title ?? 'Task'}</div>
                    <div className="text-xs text-slate-500 mt-0.5">
                      {t.status ?? '—'} {t.dueDate ? `• Due ${t.dueDate}` : ''}
                    </div>
                  </div>
                ))}
                {tasks.length > 8 && (
                  <button
                    type="button"
                    className="text-xs text-slate-600 hover:text-slate-800 underline"
                    onClick={() => router.push(`/projects/${projectId}/tasks`)}
                  >
                    View all ({tasks.length})
                  </button>
                )}
              </>
            )}
          </div>
        </Card>
      </div>

      <Card padding="md">
        <div className="flex items-start justify-between gap-3">
          <div>
            <div className="text-sm font-semibold text-slate-900">Project access</div>
            <div className="text-xs text-slate-500 mt-0.5">Manage who can view or work on this project.</div>
          </div>
          <div className="flex gap-2">
            <Button
              size="sm"
              variant="secondary"
              onClick={() => {
                setAclDraft((prev) => [...prev, { draftId: createAclDraftId(), userId: '', role: 'viewer' }]);
                setAclDirty(true);
              }}
            >
              Add user
            </Button>
            <Button
              size="sm"
              variant="primary"
              disabled={!aclDirty || updateAcl.isPending || aclDraft.length === 0}
              onClick={() => {
                if (normalizedAcl.length === 0) {
                  setAclDirty(false);
                  return;
                }
                updateAcl.mutate(normalizedAcl, {
                  onSuccess: () => setAclDirty(false),
                });
              }}
            >
              Save access
            </Button>
          </div>
        </div>

        <div className="mt-3 space-y-2">
          {aclLoading ? (
            <div className="text-sm text-slate-500 animate-pulse">Loading access…</div>
          ) : aclIsError ? (
            <div className="text-sm text-red-600">Failed to load access. {String((aclError as Error | null)?.message ?? '')}</div>
          ) : aclDraft.length === 0 ? (
            <div className="text-sm text-slate-500">No users have access yet.</div>
          ) : (
            <div className="space-y-2">
              {aclDraft.map((entry, idx) => {
                const role = entry.role;
                return (
                  <div key={entry.draftId} className="flex flex-col sm:flex-row gap-2 sm:items-center">
                    <input
                      aria-label={`User ID ${idx + 1}`}
                      value={entry.userId}
                      onChange={(e) => {
                        const v = e.target.value;
                        setAclDraft((prev) => prev.map((p, i) => (i === idx ? { ...p, userId: v } : p)));
                        setAclDirty(true);
                      }}
                      placeholder="User ID"
                      className="flex-1 rounded border border-slate-300 px-2 py-1.5 text-sm"
                    />
                    <select
                      aria-label={`Role ${idx + 1}`}
                      value={role}
                      onChange={(e) => {
                        const v = e.target.value as ProjectAclRole;
                        setAclDraft((prev) => prev.map((p, i) => (i === idx ? { ...p, role: v } : p)));
                        setAclDirty(true);
                      }}
                      className="rounded border border-slate-300 px-2 py-1.5 text-sm"
                    >
                      {(['owner', 'admin', 'editor', 'viewer'] as ProjectAclRole[]).map((r) => (
                        <option key={r} value={r}>
                          {r}
                        </option>
                      ))}
                    </select>
                    <Button
                      size="sm"
                      variant="secondary"
                      onClick={() => {
                        setAclDraft((prev) => prev.filter((_, i) => i !== idx));
                        setAclDirty(true);
                      }}
                    >
                      Remove
                    </Button>
                  </div>
                );
              })}
            </div>
          )}

          {updateAcl.isError ? (
            <div className="text-xs text-red-600 mt-2">{String((updateAcl.error as Error | null)?.message ?? '')}</div>
          ) : null}
        </div>
      </Card>
    </div>
  );
}

