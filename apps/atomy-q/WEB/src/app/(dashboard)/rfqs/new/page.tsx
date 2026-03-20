'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import { Card } from '@/components/ds/Card';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';
import { SelectInput, TextInput } from '@/components/ds/Input';
import { useProjects } from '@/hooks/use-projects';
import { useFeatureFlags } from '@/hooks/use-feature-flags';
import { api } from '@/lib/api';
import { Button } from '@/components/ds/Button';

export default function NewRfqPage() {
  const router = useRouter();
  const { data: flags, isLoading: flagsLoading } = useFeatureFlags();
  const projectsEnabled = flags?.projects === true;
  const { data: projects = [] } = useProjects({ enabled: !flagsLoading && projectsEnabled });
  const [projectId, setProjectId] = React.useState('');
  const [title, setTitle] = React.useState('');
  const [isSubmitting, setIsSubmitting] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);

  async function onCreate() {
    setIsSubmitting(true);
    setError(null);
    try {
      const payload: Record<string, unknown> = { title };
      if (projectId) payload.project_id = projectId;
      const { data } = await api.post('/rfqs', payload);
      const id = String(data?.data?.id ?? data?.id ?? '');
      if (id) {
        router.push(`/rfqs/${encodeURIComponent(id)}/overview`);
        return;
      }
      router.push('/rfqs');
    } catch (e: unknown) {
      const axiosish = e as { response?: { data?: Record<string, unknown> } } & { message?: string };
      const data = axiosish?.response?.data;
      const msgRaw =
        (typeof data?.error === 'string' && data.error.trim() ? data.error : null) ??
        (typeof data?.message === 'string' && data.message.trim() ? data.message : null) ??
        (data?.errors ? 'Validation failed' : null) ??
        (typeof axiosish?.message === 'string' && axiosish.message.trim() ? axiosish.message : null);
      setError(msgRaw && String(msgRaw).trim() ? String(msgRaw) : 'Failed to create RFQ');
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <div className="space-y-4">
      <WorkspaceBreadcrumbs
        items={[
          { label: 'RFQs', href: '/rfqs' },
          { label: 'New RFQ' },
        ]}
      />

      <Card padding="md">
        <h1 className="text-lg font-semibold text-slate-900">Create New RFQ</h1>
        <p className="text-sm text-slate-500 mt-1">Create a draft RFQ. You can attach a project optionally.</p>

        <div className="mt-4 max-w-md">
          <TextInput
            label="Title"
            value={title}
            onChange={(e) => setTitle(e.target.value)}
            placeholder="e.g. Laptops for Q3 onboarding"
          />

          {projectsEnabled ? (
            <div className="mt-3">
              <SelectInput
                label="Project (optional)"
                value={projectId}
                onChange={(e) => setProjectId(e.target.value)}
                placeholder="No project"
                options={projects.map((p) => ({ value: p.id, label: p.name }))}
              />
            </div>
          ) : null}

          {error && <div className="mt-3 text-sm text-red-600">{error}</div>}

          <div className="mt-4 flex items-center gap-2">
            <Button size="sm" variant="primary" onClick={onCreate} disabled={!title.trim() || isSubmitting}>
              {isSubmitting ? 'Creating…' : 'Create RFQ'}
            </Button>
            <Button size="sm" variant="secondary" onClick={() => router.push('/rfqs')} disabled={isSubmitting}>
              Cancel
            </Button>
          </div>
        </div>
      </Card>
    </div>
  );
}
