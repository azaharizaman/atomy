'use client';

import React from 'react';
import Link from 'next/link';
import { CheckCircle2, Upload, BarChart2, Send, FileText, ShieldCheck, AlertTriangle } from 'lucide-react';

import { EmptyState } from '@/components/ds/Card';
import { PageHeader } from '@/components/ds/FilterBar';
import { KPIScorecard } from '@/components/ds/KPIScorecard';
import { StatusBadge } from '@/components/ds/Badge';
import { SectionCard } from '@/components/ds/Card';
import { Timeline, type TimelineEvent } from '@/components/ds/Timeline';
import { AiNarrativePanel } from '@/components/ai/ai-narrative-panel';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';
import { OverviewNextStep } from '@/components/workspace/overview-next-step';
import { useRfqAiSummary } from '@/hooks/use-rfq-ai-summary';
import { useRfqOverview, type RfqOverviewActivityItem } from '@/hooks/use-rfq-overview';
import { RfqScheduleTimeline } from '@/components/workspace/rfq-schedule-timeline';

function formatRelativeTime(iso: string): string {
  const date = new Date(iso);
  const now = Date.now();
  const diffMs = now - date.getTime();
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMs / 3600000);
  const diffDays = Math.floor(diffMs / 86400000);
  if (diffMins < 1) return 'Just now';
  if (diffMins < 60) return `${diffMins} min ago`;
  if (diffHours < 24) return `${diffHours} hour${diffHours !== 1 ? 's' : ''} ago`;
  if (diffDays < 7) return `${diffDays} day${diffDays !== 1 ? 's' : ''} ago`;
  return date.toLocaleDateString();
}

function activityToTimelineEvent(item: RfqOverviewActivityItem): TimelineEvent {
  const timestamp = formatRelativeTime(item.timestamp);
  const iconByType: Record<string, { icon: React.ReactNode; color: TimelineEvent['iconColor'] }> = {
    quote: { icon: <Upload size={12} />, color: 'indigo' },
    invitation: { icon: <Send size={12} />, color: 'indigo' },
    comparison: { icon: <BarChart2 size={12} />, color: 'indigo' },
    approval: { icon: <ShieldCheck size={12} />, color: 'green' },
    creation: { icon: <FileText size={12} />, color: 'slate' },
    system: { icon: <CheckCircle2 size={12} />, color: 'green' },
  };
  const { icon, color } = iconByType[item.type] ?? { icon: <CheckCircle2 size={12} />, color: 'slate' as const };
  return {
    id: item.id,
    timestamp,
    actor: item.actor,
    action: item.action,
    icon,
    iconColor: color,
  };
}

export default function RfqOverviewPage({ params }: { params: Promise<{ rfqId: string }> }) {
  const { rfqId } = React.use(params);
  const { data: overview, isLoading, isError, error } = useRfqOverview(rfqId);
  const rfqAiSummary = useRfqAiSummary(rfqId);

  const rfq = overview?.rfq;
  const expectedQuotes = overview?.expected_quotes ?? 0;
  const quoteCount = overview?.normalization?.total_quotes ?? rfq?.quotes_count ?? 0;
  const acceptedQuotes = overview?.normalization?.accepted_count ?? 0;
  const normProgress = overview?.normalization?.progress_pct ?? 0;
  const quoteProgress = expectedQuotes > 0 ? Math.round((quoteCount / expectedQuotes) * 100) : 0;
  const comparison = overview?.comparison;
  const approvals = overview?.approvals;
  const activityEvents = (overview?.activity ?? []).map(activityToTimelineEvent);

  const scheduleContext = React.useMemo(
    () => ({
      status: rfq?.status ?? 'draft',
      submission_deadline: rfq?.submission_deadline,
      closing_date: rfq?.closing_date,
      expected_award_at: rfq?.expected_award_at,
      technical_review_due_at: rfq?.technical_review_due_at,
      financial_review_due_at: rfq?.financial_review_due_at,
      needs_review_count: overview?.normalization?.needs_review_count,
      comparison_is_preview: comparison != null ? Boolean(comparison.is_preview) : null,
      approval_overall: approvals?.overall ?? 'none',
    }),
    [
      rfq?.status,
      rfq?.submission_deadline,
      rfq?.closing_date,
      rfq?.expected_award_at,
      rfq?.technical_review_due_at,
      rfq?.financial_review_due_at,
      overview?.normalization?.needs_review_count,
      comparison?.is_preview,
      approvals?.overall,
    ],
  );

  const breadcrumbItems = [
    { label: 'RFQs', href: '/rfqs' },
    { label: rfq?.title ?? 'Requisition', href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
    { label: 'Overview' },
  ];

  if (isLoading) {
    return (
      <div className="space-y-5">
        <WorkspaceBreadcrumbs items={breadcrumbItems} />
        <div className="grid gap-4 xl:grid-cols-4 md:grid-cols-2">
          {[1, 2, 3, 4].map((i) => (
            <div key={i} className="h-28 rounded-lg border border-slate-200 bg-slate-50 animate-pulse" />
          ))}
        </div>
        <div className="h-48 rounded-lg border border-slate-200 bg-slate-50 animate-pulse" />
      </div>
    );
  }

  if (isError || !overview) {
    const errorMessage = error instanceof Error ? error.message : `RFQ overview unavailable for "${rfqId}".`;

    return (
      <div className="space-y-5">
        <WorkspaceBreadcrumbs items={breadcrumbItems} />
        <PageHeader title="Overview" subtitle="RFQ overview unavailable" />
        <SectionCard title="Overview unavailable" subtitle="The overview screen could not load the live RFQ payload.">
          <EmptyState
            icon={<AlertTriangle size={20} />}
            title="Could not load RFQ overview"
            description={errorMessage}
          />
        </SectionCard>
      </div>
    );
  }

  const approvalLabel =
    approvals?.overall === 'pending'
      ? `${approvals.pending_count} pending`
      : approvals?.overall === 'approved'
        ? 'Approved'
        : approvals?.overall === 'rejected'
          ? 'Rejected'
          : 'None';
  const approvalStatusBadge =
    approvals?.overall === 'pending'
      ? 'pending'
      : approvals?.overall === 'approved'
        ? 'approved'
        : approvals?.overall === 'rejected'
          ? 'rejected'
          : 'draft';

  return (
    <div className="space-y-5">
      <WorkspaceBreadcrumbs items={breadcrumbItems} />

      {/* Next step + deadline */}
      <OverviewNextStep
        rfqId={rfqId}
        status={rfq?.status ?? 'draft'}
        approvalOverall={approvals?.overall ?? 'none'}
        hasComparisonRun={comparison != null}
        comparisonIsPreview={comparison?.is_preview ?? false}
        submissionDeadline={rfq?.submission_deadline ?? undefined}
      />

      <SectionCard title="Schedule" subtitle="Deadlines, planning milestones, and today on one axis">
        <RfqScheduleTimeline {...scheduleContext} />
      </SectionCard>

      <AiNarrativePanel
        featureKey="rfq_ai_insights"
        title="RFQ Insight Summary"
        subtitle="Assistive interpretation of the latest RFQ signals."
        summary={rfqAiSummary.summary}
        isLoading={rfqAiSummary.isLoading}
        isError={rfqAiSummary.isError}
        error={rfqAiSummary.error}
        fallbackCopy="RFQ insights are unavailable. The deterministic RFQ overview remains usable."
      />

      {/* 4 KPI scorecards */}
      <div className="grid gap-4 xl:grid-cols-4 md:grid-cols-2">
        <Link href={`/rfqs/${encodeURIComponent(rfqId)}/quote-intake`} className="block">
          <KPIScorecard
            title="Quotes received"
            value={quoteCount}
            subtitle={`Expected ${expectedQuotes}`}
            progress={{ value: quoteProgress, type: 'bar' }}
          />
        </Link>
        <Link href={`/rfqs/${encodeURIComponent(rfqId)}/quote-intake`} className="block">
          <KPIScorecard
            title="Normalization progress"
            value={`${acceptedQuotes}/${quoteCount}`}
            subtitle="Accepted quotes ready for comparison"
            progress={{ value: normProgress, type: 'bar' }}
          />
        </Link>
        <Link href={`/rfqs/${encodeURIComponent(rfqId)}/comparison-runs`} className="block">
          <KPIScorecard
            title="Comparison status"
            value={comparison?.name ?? '—'}
            subtitle={comparison ? (comparison.is_preview ? 'Preview run' : 'Latest run') : 'No runs yet'}
            badge={
              comparison ? (
                <StatusBadge
                  status={comparison.status === 'locked' ? 'approved' : comparison.is_preview ? 'preview' : 'draft'}
                  label={comparison.status}
                />
              ) : undefined
            }
          />
        </Link>
        <Link href={`/rfqs/${encodeURIComponent(rfqId)}/approvals`} className="block">
          <KPIScorecard
            title="Approval status"
            value={approvalLabel}
            subtitle={approvals?.overall === 'pending' ? 'Action required' : 'No gates triggered'}
            badge={<StatusBadge status={approvalStatusBadge} label={approvalLabel} />}
          />
        </Link>
      </div>

      {/* Activity timeline */}
      <SectionCard
        title="Activity timeline"
        subtitle="Chronological feed for this RFQ"
      >
        {activityEvents.length > 0 ? (
          <Timeline events={activityEvents} />
        ) : (
          <p className="text-sm text-slate-500 py-4">No activity yet.</p>
        )}
      </SectionCard>
    </div>
  );
}
