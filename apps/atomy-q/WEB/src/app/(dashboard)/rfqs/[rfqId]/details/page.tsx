'use client';

import React from 'react';
import { useForm } from 'react-hook-form';
import { z } from 'zod';
import { zodResolver } from '@hookform/resolvers/zod';
import { toast } from 'sonner';

import { PageHeader } from '@/components/ds/FilterBar';
import { SectionCard, InfoGrid } from '@/components/ds/Card';
import { Button } from '@/components/ds/Button';
import { TextAreaInput, TextInput } from '@/components/ds/Input';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';
import { useRfq, useUpdateRfq, type RfqDetail } from '@/hooks/use-rfq';
import { datetimeLocalToIsoOrNull, formatScheduleInstant, isoOrNullToDatetimeLocal } from '@/lib/datetime-local';
import { parseApiError } from '@/lib/api-error';
import { SquarePen, X } from 'lucide-react';

const scheduleFormSchema = z.object({
  title: z.string().min(1, 'Title is required'),
  category: z.string().optional(),
  description: z.string().optional(),
  submission_deadline: z.string().optional(),
  closing_date: z.string().optional(),
  expected_award_at: z.string().optional(),
  technical_review_due_at: z.string().optional(),
  financial_review_due_at: z.string().optional(),
});

type ScheduleFormValues = z.infer<typeof scheduleFormSchema>;

function defaultsFromRfq(rfq: RfqDetail): ScheduleFormValues {
  return {
    title: rfq.title,
    category: rfq.category ?? '',
    description: rfq.description ?? '',
    submission_deadline: isoOrNullToDatetimeLocal(rfq.submission_deadline),
    closing_date: isoOrNullToDatetimeLocal(rfq.closing_date),
    expected_award_at: isoOrNullToDatetimeLocal(rfq.expected_award_at),
    technical_review_due_at: isoOrNullToDatetimeLocal(rfq.technical_review_due_at),
    financial_review_due_at: isoOrNullToDatetimeLocal(rfq.financial_review_due_at),
  };
}

const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

export default function RfqDetailsPage({ params }: { params: Promise<{ rfqId: string }> }) {
  const { rfqId } = React.use(params);
  const { data: rfq, isLoading, isError, error } = useRfq(rfqId);
  const updateRfq = useUpdateRfq(rfqId);
  const [isEditing, setIsEditing] = React.useState(false);

  const form = useForm<ScheduleFormValues>({
    resolver: zodResolver(scheduleFormSchema),
    defaultValues: {
      title: '',
      category: '',
      description: '',
      submission_deadline: '',
      closing_date: '',
      expected_award_at: '',
      technical_review_due_at: '',
      financial_review_due_at: '',
    },
  });

  const openEdit = React.useCallback(() => {
    if (!rfq) return;
    form.reset(defaultsFromRfq(rfq));
    setIsEditing(true);
  }, [form, rfq]);

  const cancelEdit = React.useCallback(() => {
    setIsEditing(false);
    if (rfq) form.reset(defaultsFromRfq(rfq));
  }, [form, rfq]);

  React.useEffect(() => {
    if (!isEditing && rfq) {
      form.reset(defaultsFromRfq(rfq));
    }
  }, [rfq, isEditing, form]);

  const onSubmit = form.handleSubmit(async (values) => {
    if (useMocks) {
      toast.error('Turn off NEXT_PUBLIC_USE_MOCKS to save changes to the API.');
      return;
    }
    try {
      await updateRfq.mutateAsync({
        title: values.title.trim(),
        category: values.category?.trim() ? values.category.trim() : null,
        description: values.description?.trim() ? values.description.trim() : null,
        submission_deadline: datetimeLocalToIsoOrNull(values.submission_deadline ?? ''),
        closing_date: datetimeLocalToIsoOrNull(values.closing_date ?? ''),
        expected_award_at: datetimeLocalToIsoOrNull(values.expected_award_at ?? ''),
        technical_review_due_at: datetimeLocalToIsoOrNull(values.technical_review_due_at ?? ''),
        financial_review_due_at: datetimeLocalToIsoOrNull(values.financial_review_due_at ?? ''),
      });
      toast.success('RFQ updated');
      setIsEditing(false);
    } catch (e) {
      const p = parseApiError(e);
      const firstField =
        p.fieldErrors &&
        Object.values(p.fieldErrors).find((msgs) => Array.isArray(msgs) && msgs.length > 0)?.[0];
      toast.error(firstField ?? p.message ?? 'Could not save RFQ');
    }
  });

  const breadcrumbItems = [
    { label: 'RFQs', href: '/rfqs' },
    { label: rfq?.title ?? 'Requisition', href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
    { label: 'Details' },
  ];

  if (isLoading) {
    return (
      <div className="space-y-5">
        <WorkspaceBreadcrumbs items={breadcrumbItems} />
        <div className="h-10 w-64 rounded-md bg-slate-100 animate-pulse" />
        <div className="grid gap-5 xl:grid-cols-2">
          <div className="h-56 rounded-lg border border-slate-200 bg-slate-50 animate-pulse" />
          <div className="h-56 rounded-lg border border-slate-200 bg-slate-50 animate-pulse" />
        </div>
      </div>
    );
  }

  if (isError || !rfq) {
    return (
      <div className="space-y-5">
        <WorkspaceBreadcrumbs items={breadcrumbItems} />
        <PageHeader title="RFQ details" subtitle="Could not load this RFQ" />
        <p className="text-sm text-slate-600">
          {error instanceof Error ? error.message : 'Try again from the RFQ list.'}
        </p>
      </div>
    );
  }

  const viewMetadata = [
    { label: 'RFQ number', value: rfq.rfq_number ?? rfq.id },
    { label: 'Category', value: rfq.category?.trim() ? rfq.category : '—' },
    { label: 'Estimated value', value: rfq.estValue ?? '—' },
    { label: 'Savings', value: rfq.savings ?? '—' },
    { label: 'Project', value: rfq.projectName?.trim() ? rfq.projectName : '—' },
  ];

  const viewSchedule = [
    { label: 'Submission deadline', value: formatScheduleInstant(rfq.submission_deadline) },
    { label: 'Closing date', value: formatScheduleInstant(rfq.closing_date) },
    { label: 'Technical review due', value: formatScheduleInstant(rfq.technical_review_due_at) },
    { label: 'Financial review due', value: formatScheduleInstant(rfq.financial_review_due_at) },
    { label: 'Expected award', value: formatScheduleInstant(rfq.expected_award_at) },
  ];

  return (
    <div className="space-y-5">
      <WorkspaceBreadcrumbs items={breadcrumbItems} />
      <PageHeader
        title="RFQ details"
        subtitle="Metadata, commercial fields, and schedule milestones"
        actions={
          !isEditing ? (
            <Button variant="outline" size="sm" type="button" onClick={openEdit}>
              <SquarePen size={14} className="mr-1.5" />
              Edit
            </Button>
          ) : (
            <div className="flex flex-wrap items-center gap-2">
              <Button variant="ghost" size="sm" type="button" onClick={cancelEdit} disabled={updateRfq.isPending}>
                <X size={14} className="mr-1.5" />
                Cancel
              </Button>
              <Button variant="primary" size="sm" type="submit" form="rfq-details-form" loading={updateRfq.isPending}>
                Save changes
              </Button>
            </div>
          )
        }
      />

      {useMocks && (
        <p className="text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-md px-3 py-2">
          Mock data is on: schedule fields load from seed, but <strong>Save</strong> needs a live API (
          <code className="text-[11px]">NEXT_PUBLIC_USE_MOCKS=false</code>).
        </p>
      )}

      {!isEditing ? (
        <>
          <div className="grid gap-5 xl:grid-cols-[1fr,360px]">
            <SectionCard title="Commercial metadata">
              <InfoGrid cols={2} items={viewMetadata} />
            </SectionCard>
            <SectionCard title="Description">
              <p className="text-sm leading-6 text-slate-600 p-4 pt-0 whitespace-pre-wrap">
                {rfq.description?.trim() ? rfq.description.trim() : 'No description provided.'}
              </p>
            </SectionCard>
          </div>
          <SectionCard
            title="Schedule & deadlines"
            subtitle="Shown on the overview timeline; edit here to add or clear dates"
          >
            <InfoGrid cols={2} items={viewSchedule} />
          </SectionCard>
        </>
      ) : (
        <form id="rfq-details-form" onSubmit={onSubmit} className="space-y-5">
          <div className="grid gap-5 xl:grid-cols-[1fr,360px]">
            <SectionCard title="Commercial metadata">
              <div className="p-4 pt-0 space-y-4">
                <TextInput
                  id="rfq-detail-title"
                  label="Title"
                  required
                  {...form.register('title')}
                  error={form.formState.errors.title?.message}
                />
                <TextInput
                  id="rfq-detail-category"
                  label="Category"
                  {...form.register('category')}
                  hint="Optional. Leave blank to clear."
                />
                <p className="text-xs text-slate-500">
                  Estimated value and project are not editable here yet; use downstream flows or API as needed.
                </p>
              </div>
            </SectionCard>
            <SectionCard title="Description">
              <div className="p-4 pt-0">
                <TextAreaInput
                  id="rfq-detail-description"
                  label="Description"
                  rows={6}
                  {...form.register('description')}
                  hint="Optional. Clear the text and save to remove the stored description."
                />
              </div>
            </SectionCard>
          </div>

          <SectionCard
            title="Schedule & deadlines"
            subtitle="Clear a field and save to remove that date on the server"
          >
            <div className="p-4 pt-0 space-y-4">
              <div className="grid gap-4 sm:grid-cols-2">
                <TextInput
                  id="rfq-submission-deadline"
                  label="Submission deadline"
                  type="datetime-local"
                  step={60}
                  {...form.register('submission_deadline')}
                />
                <TextInput
                  id="rfq-closing-date"
                  label="Closing date"
                  type="datetime-local"
                  step={60}
                  {...form.register('closing_date')}
                />
                <TextInput
                  id="rfq-technical-review"
                  label="Technical review due"
                  type="datetime-local"
                  step={60}
                  {...form.register('technical_review_due_at')}
                />
                <TextInput
                  id="rfq-financial-review"
                  label="Financial review due"
                  type="datetime-local"
                  step={60}
                  {...form.register('financial_review_due_at')}
                />
                <TextInput
                  id="rfq-expected-award"
                  label="Expected award"
                  type="datetime-local"
                  step={60}
                  {...form.register('expected_award_at')}
                />
              </div>
              <p className="text-xs text-slate-500">
                Dates are sent to the API as UTC instants. Empty field removes the stored value.
              </p>
              <div className="flex gap-2 sm:hidden">
                <Button variant="primary" size="sm" type="submit" loading={updateRfq.isPending}>
                  Save changes
                </Button>
                <Button variant="ghost" size="sm" type="button" onClick={cancelEdit} disabled={updateRfq.isPending}>
                  Cancel
                </Button>
              </div>
            </div>
          </SectionCard>
        </form>
      )}
    </div>
  );
}
