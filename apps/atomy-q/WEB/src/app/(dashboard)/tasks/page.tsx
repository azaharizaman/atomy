'use client';

import React from 'react';
import { Button } from '@/components/ds/Button';
import { Card } from '@/components/ds/Card';
import { DataTable, type ColumnDef } from '@/components/ds/DataTable';
import { PageHeader } from '@/components/ds/FilterBar';
import { useTasks, type TaskListItem } from '@/hooks/use-tasks';
import { useTask } from '@/hooks/use-task';
import { useFeatureFlags } from '@/hooks/use-feature-flags';
import { useUpdateTaskStatus, TASK_STATUSES } from '@/hooks/use-update-task-status';

const TASK_COLUMNS: ColumnDef<TaskListItem>[] = [
  {
    key: 'id',
    label: 'ID',
    width: '120px',
    render: (row) => <span className="font-mono text-xs text-slate-600">{row.id}</span>,
  },
  {
    key: 'title',
    label: 'Task',
    render: (row) => <span className="text-sm font-medium text-slate-800">{row.title}</span>,
  },
  {
    key: 'status',
    label: 'Status',
    width: '120px',
    render: (row) => <span className="text-xs text-slate-600">{row.status}</span>,
  },
  {
    key: 'dueDate',
    label: 'Due',
    width: '140px',
    render: (row) => (
      <span className="text-xs text-slate-600 tabular-nums">
        {row.dueDate ? new Date(row.dueDate).toLocaleDateString() : '—'}
      </span>
    ),
  },
];

function TaskDetailDrawer({
  taskId,
  onClose,
}: {
  taskId: string;
  onClose: () => void;
}) {
  const { data: flags } = useFeatureFlags();
  const { data: task, isLoading, isError, error } = useTask(taskId, {
    enabled: flags?.tasks === true,
  });
  const updateStatus = useUpdateTaskStatus(taskId);

  return (
    <div className="fixed inset-y-0 right-0 w-full max-w-md bg-white border-l border-slate-200 shadow-lg z-30 flex flex-col">
      <div className="flex items-center justify-between p-4 border-b border-slate-200">
        <h2 className="text-sm font-semibold text-slate-900">Task detail</h2>
        <Button size="sm" variant="secondary" onClick={onClose}>
          Close
        </Button>
      </div>
      <div className="flex-1 overflow-y-auto p-4 space-y-4">
        {isLoading && <div className="text-sm text-slate-500">Loading…</div>}
        {isError && !isLoading && (
          <div className="text-sm text-red-600">Failed to load task{error ? `: ${String((error as Error).message)}` : ''}</div>
        )}
        {task && (
          <>
            <div>
              <div className="text-xs font-medium text-slate-500 mb-1">Title</div>
              <div className="text-sm font-medium text-slate-900">{task.title}</div>
            </div>
            {task.description && (
              <div>
                <div className="text-xs font-medium text-slate-500 mb-1">Description</div>
                <div className="text-sm text-slate-700 whitespace-pre-wrap">{task.description}</div>
              </div>
            )}
            <div>
              <div className="text-xs font-medium text-slate-500 mb-1">Status</div>
              <select
                value={task.status}
                onChange={(e) => {
                  const v = e.target.value as (typeof TASK_STATUSES)[number];
                  if (TASK_STATUSES.includes(v)) updateStatus.mutate(v);
                }}
                className="w-full rounded border border-slate-300 px-2 py-1.5 text-sm"
                disabled={updateStatus.isPending}
              >
                {TASK_STATUSES.map((s) => (
                  <option key={s} value={s}>
                    {s}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <div className="text-xs font-medium text-slate-500 mb-1">Due date</div>
              <div className="text-sm text-slate-700">
                {task.dueDate ? new Date(task.dueDate).toLocaleDateString() : '—'}
              </div>
            </div>
            {task.projectId && (
              <div>
                <div className="text-xs font-medium text-slate-500 mb-1">Project</div>
                <div className="text-sm text-slate-700 font-mono">{task.projectId}</div>
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
}

export default function TasksPage() {
  const [selectedTaskId, setSelectedTaskId] = React.useState<string | null>(null);
  const { data: flags, isLoading: flagsLoading } = useFeatureFlags();
  const tasksEnabled = flags?.tasks === true;
  const { data: tasks = [], isLoading, isError, error } = useTasks({
    enabled: !flagsLoading && tasksEnabled,
  });

  if (flagsLoading) {
    return (
      <div className="space-y-4">
        <PageHeader title="Task Inbox" subtitle="Loading…" />
        <Card padding="md">
          <div className="h-24 w-full rounded bg-slate-100 animate-pulse" aria-busy />
        </Card>
      </div>
    );
  }

  if (!tasksEnabled) {
    return null;
  }

  return (
    <div className="space-y-4">
      <PageHeader
        title="Task Inbox"
        subtitle={`${tasks.length} tasks`}
      />

      <Card padding="md">
        {isError && (
          <div className="text-sm text-red-600 mb-3">{String((error as Error)?.message)}</div>
        )}
        <DataTable
          columns={TASK_COLUMNS}
          rows={tasks}
          loading={isLoading}
          selectable={false}
          expandable={false}
          showActions={false}
          onRowAction={() => {}}
          onRowClick={(row) => setSelectedTaskId(row.id)}
        />
      </Card>

      {selectedTaskId && (
        <>
          <div
            className="fixed inset-0 bg-black/20 z-20"
            onClick={() => setSelectedTaskId(null)}
            role="button"
            tabIndex={0}
            aria-label="Close task drawer"
            onKeyDown={(e) => {
              if (e.key === 'Enter' || e.key === ' ' || e.key === 'Escape') {
                e.preventDefault();
                setSelectedTaskId(null);
              }
            }}
          />
          <TaskDetailDrawer taskId={selectedTaskId} onClose={() => setSelectedTaskId(null)} />
        </>
      )}
    </div>
  );
}
