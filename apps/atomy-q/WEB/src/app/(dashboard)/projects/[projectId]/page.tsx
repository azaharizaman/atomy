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

export default function ProjectDetailPage() {
  const router = useRouter();
  const params = useParams<{ projectId: string }>();
  const projectId = params.projectId;

  const { data: project, isLoading: projectLoading, error: projectError } = useProject(projectId);
  const { data: health, isLoading: healthLoading, isError: healthIsError, error: healthError } = useProjectHealth(projectId);
  const { data: rfqs = [] } = useProjectRfqs(projectId);
  const { data: tasks = [] } = useProjectTasks(projectId);

  const isAxios404 = axios.isAxiosError(projectError) && projectError.response?.status === 404;
  const featureDisabledSignal =
    axios.isAxiosError(projectError) && (projectError.response?.data as any)?.code === 'projects_not_enabled';

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
          <Button size="sm" variant="secondary" onClick={() => router.push('/projects')}>
            Back
          </Button>
        }
      />

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
            <span className="text-xs text-red-600">{String((healthError as any)?.message ?? '')}</span>
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
            {rfqs.length === 0 ? (
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
            {tasks.length === 0 ? (
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
                    onClick={() => router.push('/projects')}
                  >
                    View all ({tasks.length})
                  </button>
                )}
              </>
            )}
          </div>
        </Card>
      </div>
    </div>
  );
}

