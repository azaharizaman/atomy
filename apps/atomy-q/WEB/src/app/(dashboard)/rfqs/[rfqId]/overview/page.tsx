'use client';

import React from 'react';
import Link from 'next/link';
import { CheckCircle2, Upload, BarChart2, Send, FileText, ShieldCheck, Calendar } from 'lucide-react';

import { KPIScorecard } from '@/components/ds/KPIScorecard';
import { StatusBadge } from '@/components/ds/Badge';
import { SectionCard } from '@/components/ds/Card';
import { Timeline, type TimelineEvent } from '@/components/ds/Timeline';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';
import { OverviewNextStep } from '@/components/workspace/overview-next-step';
import { useRfqOverview, type RfqOverviewActivityItem } from '@/hooks/use-rfq-overview';

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
  const { data: overview, isLoading } = useRfqOverview(rfqId);

  const rfq = overview?.rfq;
  const expectedQuotes = overview?.expected_quotes ?? 0;
  const quoteCount = overview?.normalization?.total_quotes ?? rfq?.quotes_count ?? 0;
  const acceptedQuotes = overview?.normalization?.accepted_count ?? 0;
  const normProgress = overview?.normalization?.progress_pct ?? 0;
  const quoteProgress = expectedQuotes > 0 ? Math.round((quoteCount / expectedQuotes) * 100) : 0;
  const comparison = overview?.comparison;
  const approvals = overview?.approvals;
  const activityEvents = (overview?.activity ?? []).map(activityToTimelineEvent);

  const breadcrumbItems = [
    { label: 'RFQs', href: '/rfqs' },
    { label: rfq?.title ?? 'Requisition', href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
    { label: 'Overview' },
  ];

  if (isLoading || !overview) {
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

      {/* Deadlines */}
      {(rfq?.submission_deadline ?? rfq?.closing_date) && (
        <SectionCard title="Deadlines" subtitle="Key dates for this RFQ">
          <div className="grid gap-4 sm:grid-cols-2 p-4 pt-0">
            {rfq?.submission_deadline && (
              <div className="flex items-start gap-3">
                <div className="shrink-0 w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center">
                  <Calendar className="text-slate-600" size={14} />
                </div>
                <div>
                  <p className="text-xs font-medium text-slate-500 uppercase tracking-wide">Submission deadline</p>
                  <p className="text-sm text-slate-800 mt-0.5">
                    {new Date(rfq.submission_deadline).toLocaleDateString(undefined, {
                      dateStyle: 'medium',
                    })}
                  </p>
                </div>
              </div>
            )}
            {rfq?.closing_date && (
              <div className="flex items-start gap-3">
                <div className="shrink-0 w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center">
                  <Calendar className="text-slate-600" size={14} />
                </div>
                <div>
                  <p className="text-xs font-medium text-slate-500 uppercase tracking-wide">Closing date</p>
                  <p className="text-sm text-slate-800 mt-0.5">
                    {new Date(rfq.closing_date).toLocaleDateString(undefined, {
                      dateStyle: 'medium',
                    })}
                  </p>
                </div>
              </div>
            )}
          </div>
        </SectionCard>
      )}

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
